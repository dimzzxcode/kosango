<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use App\Notifications\BookingNotification;
use Illuminate\Support\Facades\Notification;

test('cancelling a booking notifies the property admin', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();
    $property->admins()->attach($admin);

    $customer = User::factory()->customer()->create();
    $booking = Booking::factory()->create([
        'property_id' => $property->id,
        'user_id' => $customer->id,
        'status' => BookingStatus::PendingPayment,
    ]);

    $this->actingAs($customer)
        ->post(route('bookings.cancel', $booking->code), ['reason' => 'Berubah pikiran'])
        ->assertRedirect(route('bookings.show', $booking->code));

    Notification::assertSentTo($admin, BookingNotification::class);
});
