<?php

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @param  array<string, mixed>  $invoiceOverrides
 * @return array{booking: Booking, invoice: Invoice}
 */
function payableInvoice(User $user, array $invoiceOverrides = []): array
{
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'status' => BookingStatus::PendingPayment,
    ]);

    $invoice = Invoice::factory()->create(array_merge([
        'booking_id' => $booking->id,
        'amount' => 500_000,
        'paid_amount' => 0,
        'status' => InvoiceStatus::Unpaid,
        'allow_partial' => false,
    ], $invoiceOverrides));

    return ['booking' => $booking, 'invoice' => $invoice];
}

function paymentProofRoute(Booking $booking, Invoice $invoice): string
{
    return route('payments.store', [
        'booking' => $booking->code,
        'invoice' => $invoice->id,
    ]);
}

test('guests cannot upload a payment proof', function () {
    $user = User::factory()->create();
    ['booking' => $booking, 'invoice' => $invoice] = payableInvoice($user);

    $this->post(paymentProofRoute($booking, $invoice), [
        'amount' => 500_000,
        'proof' => UploadedFile::fake()->image('transfer.jpg'),
    ])->assertRedirect(route('login'));

    expect(Payment::query()->count())->toBe(0);
});

test('a guest can upload a transfer proof for their own invoice', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    ['booking' => $booking, 'invoice' => $invoice] = payableInvoice($user);

    $this->actingAs($user)
        ->from(route('bookings.show', $booking->code))
        ->post(paymentProofRoute($booking, $invoice), [
            'amount' => 500_000,
            'proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])
        ->assertRedirect(route('bookings.show', $booking->code));

    $payment = Payment::query()->sole();

    expect($payment->status)->toBe(PaymentStatus::WaitingVerification)
        ->and($payment->amount)->toBe(500_000)
        ->and($payment->proof_path)->not->toBeNull()
        ->and($payment->paid_at)->toBeNull()
        ->and($invoice->refresh()->status)->toBe(InvoiceStatus::Unpaid);

    Storage::disk('local')->assertExists($payment->proof_path);
});

test('a guest cannot upload a proof for another guests invoice', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    ['booking' => $booking, 'invoice' => $invoice] = payableInvoice($owner);

    $this->actingAs($otherUser)
        ->post(paymentProofRoute($booking, $invoice), [
            'amount' => 500_000,
            'proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])
        ->assertForbidden();

    expect(Payment::query()->count())->toBe(0);
});

test('the transfer amount must match the remaining balance of a full invoice', function () {
    $user = User::factory()->create();
    ['booking' => $booking, 'invoice' => $invoice] = payableInvoice($user);

    $this->actingAs($user)
        ->from(route('bookings.show', $booking->code))
        ->post(paymentProofRoute($booking, $invoice), [
            'amount' => 100_000,
            'proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])
        ->assertRedirect(route('bookings.show', $booking->code))
        ->assertSessionHasErrors('amount');

    expect(Payment::query()->count())->toBe(0);
});

test('a partial invoice enforces the minimum first payment', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    ['booking' => $booking, 'invoice' => $invoice] = payableInvoice($user, [
        'amount' => 1_000_000,
        'allow_partial' => true,
    ]);

    $this->actingAs($user)
        ->from(route('bookings.show', $booking->code))
        ->post(paymentProofRoute($booking, $invoice), [
            'amount' => 400_000,
            'proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])
        ->assertSessionHasErrors('amount');

    expect(Payment::query()->count())->toBe(0);

    $this->actingAs($user)
        ->post(paymentProofRoute($booking, $invoice), [
            'amount' => 500_000,
            'proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])
        ->assertSessionHasNoErrors();

    expect(Payment::query()->sole()->amount)->toBe(500_000);
});

test('an invoice with a payment awaiting verification rejects another upload', function () {
    $user = User::factory()->create();
    ['booking' => $booking, 'invoice' => $invoice] = payableInvoice($user);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 500_000,
        'status' => PaymentStatus::WaitingVerification,
    ]);

    $this->actingAs($user)
        ->post(paymentProofRoute($booking, $invoice), [
            'amount' => 500_000,
            'proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])
        ->assertSessionHasErrors('amount');

    expect(Payment::query()->count())->toBe(1);
});

test('uploading a proof is locked after three rejected uploads', function () {
    $user = User::factory()->create();
    ['booking' => $booking, 'invoice' => $invoice] = payableInvoice($user);

    Payment::factory()->rejected()->count(3)->create([
        'invoice_id' => $invoice->id,
    ]);

    $this->actingAs($user)
        ->post(paymentProofRoute($booking, $invoice), [
            'amount' => 500_000,
            'proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])
        ->assertSessionHasErrors('proof');

    expect(Payment::query()->count())->toBe(3);
});

test('a paid invoice cannot receive another proof', function () {
    $user = User::factory()->create();
    ['booking' => $booking, 'invoice' => $invoice] = payableInvoice($user, [
        'status' => InvoiceStatus::Paid,
        'paid_amount' => 500_000,
    ]);

    $this->actingAs($user)
        ->post(paymentProofRoute($booking, $invoice), [
            'amount' => 500_000,
            'proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])
        ->assertSessionHasErrors('invoice');

    expect(Payment::query()->count())->toBe(0);
});
