<?php

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentPlan;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;

/**
 * @return array{booking: Booking, invoice: Invoice, payment: Payment, admin: User}
 */
function installmentPayment(int $paidAmount, string $plan = PaymentPlan::Installment->value): array
{
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();
    $property->admins()->attach($admin);

    $booking = Booking::factory()->create([
        'property_id' => $property->id,
        'status' => BookingStatus::PendingPayment,
        'payment_plan' => $plan,
        'total_price' => 2_000_000,
        'expires_at' => now()->addDay(),
    ]);

    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'amount' => 2_000_000,
        'paid_amount' => 0,
        'status' => InvoiceStatus::Unpaid,
        'allow_partial' => $plan === PaymentPlan::Installment->value,
        'due_date' => today()->addDays(30),
        'grace_until' => today()->addDays(37),
    ]);

    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => $paidAmount,
        'status' => PaymentStatus::WaitingVerification,
    ]);

    return ['booking' => $booking, 'invoice' => $invoice, 'payment' => $payment, 'admin' => $admin];
}

test('an installment payment below half does not confirm the booking', function () {
    ['booking' => $booking, 'payment' => $payment, 'admin' => $admin] = installmentPayment(999_999);

    $this->actingAs($admin)
        ->post(route('admin.payments.verify', $payment), ['action' => 'approve'])
        ->assertRedirect();

    expect($booking->refresh()->status)->toBe(BookingStatus::PendingPayment)
        ->and($booking->confirmed_at)->toBeNull();
});

test('an installment payment of exactly half confirms the booking', function () {
    ['booking' => $booking, 'payment' => $payment, 'admin' => $admin] = installmentPayment(1_000_000);

    $this->actingAs($admin)
        ->post(route('admin.payments.verify', $payment), ['action' => 'approve'])
        ->assertRedirect();

    expect($booking->refresh()->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->confirmed_at)->not->toBeNull();
});

test('a second approved payment does not upsert or re-confirm the booking', function () {
    ['booking' => $booking, 'invoice' => $invoice, 'payment' => $payment, 'admin' => $admin] = installmentPayment(1_000_000);

    $this->actingAs($admin)
        ->post(route('admin.payments.verify', $payment), ['action' => 'approve'])
        ->assertRedirect();

    $confirmedAt = $booking->refresh()->confirmed_at;

    $second = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 500_000,
        'status' => PaymentStatus::WaitingVerification,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.payments.verify', $second), ['action' => 'approve'])
        ->assertRedirect();

    expect($booking->refresh()->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->confirmed_at->equalTo($confirmedAt))->toBeTrue()
        ->and($invoice->refresh()->paid_amount)->toBe(1_500_000);
});
