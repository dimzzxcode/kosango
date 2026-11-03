<?php

use App\Enums\BookingPeriod;
use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentPlan;
use App\Enums\PaymentStatus;
use App\Enums\PropertyStatus;
use App\Enums\RefundInitiator;
use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\RoomTypePrice;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * @return array{property: Property, roomType: RoomType, room: Room}
 */
function bookableRoom(int $price = 500_000, int $capacity = 2): array
{
    $property = Property::factory()->create(['status' => PropertyStatus::Published]);
    $roomType = RoomType::factory()->create([
        'property_id' => $property->id,
        'capacity' => $capacity,
    ]);

    RoomTypePrice::factory()->create([
        'room_type_id' => $roomType->id,
        'period' => BookingPeriod::Daily,
        'price' => $price,
        'min_duration' => 1,
        'max_duration' => 30,
        'is_active' => true,
    ]);

    $room = Room::factory()->create([
        'property_id' => $property->id,
        'room_type_id' => $roomType->id,
    ]);

    return ['property' => $property, 'roomType' => $roomType, 'room' => $room];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bookingPayload(RoomType $roomType, Room $room, array $overrides = []): array
{
    return array_merge([
        'room_type_id' => $roomType->id,
        'room_id' => $room->id,
        'period' => BookingPeriod::Daily->value,
        'duration' => 2,
        'payment_plan' => PaymentPlan::Full->value,
        'check_in' => now()->addDays(7)->toDateString(),
        'guests' => min(2, $roomType->capacity),
        'phone' => '081234567890',
        'note' => null,
        'terms' => true,
    ], $overrides);
}

test('guests cannot create a booking', function () {
    ['roomType' => $roomType, 'room' => $room] = bookableRoom();

    $this->post(route('bookings.store'), bookingPayload($roomType, $room))
        ->assertRedirect(route('login'));

    expect(Booking::query()->count())->toBe(0);
});

test('a user can create a full payment booking with one invoice', function () {
    $user = User::factory()->create();
    ['property' => $property, 'roomType' => $roomType, 'room' => $room] = bookableRoom(500_000);

    $this->actingAs($user)
        ->post(route('bookings.store'), bookingPayload($roomType, $room))
        ->assertRedirect();

    $booking = Booking::query()->sole();

    expect($booking->user_id)->toBe($user->id)
        ->and($booking->property_id)->toBe($property->id)
        ->and($booking->duration)->toBe(2)
        ->and($booking->unit_price)->toBe(500_000)
        ->and($booking->total_price)->toBe(1_000_000)
        ->and($booking->status)->toBe(BookingStatus::PendingPayment)
        ->and($booking->terms_accepted_at)->not->toBeNull()
        ->and(Str::startsWith($booking->code, 'BK-'))->toBeTrue();

    $invoice = $booking->invoices()->sole();

    expect($invoice->amount)->toBe(1_000_000)
        ->and($invoice->status)->toBe(InvoiceStatus::Unpaid)
        ->and($invoice->allow_partial)->toBeFalse()
        ->and($invoice->due_date->toDateString())->toBe(now()->addDays(6)->toDateString());
});

test('a per period plan creates one invoice per rental period', function () {
    $user = User::factory()->create();
    ['roomType' => $roomType, 'room' => $room] = bookableRoom(400_000);

    $this->actingAs($user)
        ->post(route('bookings.store'), bookingPayload($roomType, $room, [
            'duration' => 3,
            'payment_plan' => PaymentPlan::PerPeriod->value,
        ]))
        ->assertRedirect();

    $booking = Booking::query()->sole();
    $invoices = $booking->invoices()->orderBy('due_date')->get();

    expect($invoices)->toHaveCount(3)
        ->and($invoices->sum('amount'))->toBe($booking->total_price)
        ->and($invoices->every(fn (Invoice $invoice) => $invoice->amount === 400_000))->toBeTrue();
});

test('a yearly booking can use the installment plan with partial payment enabled', function () {
    $user = User::factory()->create();
    ['roomType' => $roomType, 'room' => $room] = bookableRoom();

    RoomTypePrice::factory()->create([
        'room_type_id' => $roomType->id,
        'period' => BookingPeriod::Yearly,
        'price' => 2_400_000,
        'min_duration' => 1,
        'max_duration' => 5,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('bookings.store'), bookingPayload($roomType, $room, [
            'period' => BookingPeriod::Yearly->value,
            'duration' => 1,
            'payment_plan' => PaymentPlan::Installment->value,
            'check_in' => now()->addDays(20)->toDateString(),
        ]))
        ->assertRedirect();

    $booking = Booking::query()->sole();
    $invoice = $booking->invoices()->sole();

    expect($booking->total_price)->toBe(2_400_000)
        ->and($invoice->amount)->toBe(2_400_000)
        ->and($invoice->allow_partial)->toBeTrue();
});

test('a booking is rejected when the room is already booked for those dates', function () {
    $user = User::factory()->create();
    ['property' => $property, 'roomType' => $roomType, 'room' => $room] = bookableRoom();

    Booking::factory()->create([
        'property_id' => $property->id,
        'room_type_id' => $roomType->id,
        'room_id' => $room->id,
        'check_in' => now()->addDays(6)->startOfDay(),
        'check_out' => now()->addDays(10)->startOfDay(),
        'status' => BookingStatus::Confirmed,
    ]);

    $this->actingAs($user)
        ->from(route('properties.show', $property->slug))
        ->post(route('bookings.store'), bookingPayload($roomType, $room))
        ->assertRedirect(route('properties.show', $property->slug))
        ->assertSessionHasErrors('room_id');

    expect(Booking::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('a user cannot view another guests booking', function () {
    $booking = Booking::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get(route('bookings.show', $booking->code))
        ->assertNotFound();
});

test('cancelling a confirmed booking within the refund window creates a full refund', function () {
    $user = User::factory()->create();
    ['property' => $property, 'roomType' => $roomType, 'room' => $room] = bookableRoom();

    $booking = Booking::factory()->confirmed()->create([
        'property_id' => $property->id,
        'room_type_id' => $roomType->id,
        'room_id' => $room->id,
        'user_id' => $user->id,
        'total_price' => 1_000_000,
        'confirmed_at' => now(),
        'check_in' => now()->addDays(7)->startOfDay(),
        'check_out' => now()->addDays(9)->startOfDay(),
    ]);

    Invoice::factory()->create([
        'booking_id' => $booking->id,
        'amount' => 1_000_000,
        'paid_amount' => 300_000,
        'status' => InvoiceStatus::Partial,
    ]);

    $this->actingAs($user)
        ->post(route('bookings.cancel', $booking->code), [
            'reason' => 'Rencana berubah',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Rani Puspita',
        ])
        ->assertRedirect(route('bookings.show', $booking->code));

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->cancelled_at)->not->toBeNull()
        ->and($booking->cancel_reason)->toBe('Rencana berubah')
        ->and($booking->refund)->not->toBeNull()
        ->and($booking->refund->amount)->toBe(300_000)
        ->and($booking->refund->status)->toBe(RefundStatus::Requested)
        ->and($booking->refund->initiated_by)->toBe(RefundInitiator::Customer)
        ->and($booking->refund->bank_name)->toBe('BCA');
});

test('cancelling a pending booking creates no refund', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'status' => BookingStatus::PendingPayment,
    ]);

    $this->actingAs($user)
        ->post(route('bookings.cancel', $booking->code), ['reason' => 'Salah pilih kamar'])
        ->assertRedirect(route('bookings.show', $booking->code));

    expect($booking->refresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->refund()->exists())->toBeFalse();
});

test('cancelling after the refund window cancels without a refund', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $user->id,
        'confirmed_at' => now()->subDays(4),
        'check_in' => now()->addDays(10)->startOfDay(),
        'check_out' => now()->addDays(12)->startOfDay(),
    ]);

    Invoice::factory()->create([
        'booking_id' => $booking->id,
        'amount' => 1_000_000,
        'paid_amount' => 1_000_000,
        'status' => InvoiceStatus::Paid,
    ]);

    $this->actingAs($user)
        ->post(route('bookings.cancel', $booking->code))
        ->assertRedirect(route('bookings.show', $booking->code));

    expect($booking->refresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->refund)->toBeNull();
});

test('a booking with a payment awaiting verification cannot be cancelled', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'status' => BookingStatus::PendingPayment,
    ]);

    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'amount' => 500_000,
    ]);

    $invoice->payments()->create([
        'method' => 'manual_transfer',
        'status' => PaymentStatus::WaitingVerification,
        'amount' => 500_000,
        'proof_path' => 'payment-proofs/demo.jpg',
    ]);

    $this->actingAs($user)
        ->post(route('bookings.cancel', $booking->code))
        ->assertSessionHasErrors('booking');

    expect($booking->refresh()->status)->toBe(BookingStatus::PendingPayment);
});
