<?php

namespace App\Listeners;

use App\Enums\BookingStatus;
use App\Enums\PaymentPlan;
use App\Events\InvoicePaid;
use App\Services\Notifier;
use App\Services\StatusHistoryRecorder;

class ConfirmBookingOnFirstInvoicePaid
{
    public function __construct(
        private readonly StatusHistoryRecorder $histories,
        private readonly Notifier $notifier,
    ) {}

    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice->fresh(['booking']);
        $booking = $invoice->booking;

        if ($booking->status !== BookingStatus::PendingPayment) {
            return;
        }

        if ($booking->payment_plan === PaymentPlan::Installment) {
            $minimum = (int) ceil($invoice->amount * config('booking.min_down_payment_percent') / 100);

            if ($invoice->paid_amount < $minimum) {
                return;
            }
        } elseif (! $invoice->isPaid()) {
            return;
        }

        $booking->update([
            'status' => BookingStatus::Confirmed,
            'confirmed_at' => now(),
        ]);

        $this->histories->record(
            $booking,
            BookingStatus::PendingPayment->value,
            BookingStatus::Confirmed->value,
            null,
            'Pembayaran pertama disetujui.',
        );

        $this->notifier->toUser(
            $booking->user,
            'Booking '.$booking->code.' terkonfirmasi',
            "Pembayaran pertama untuk booking {$booking->code} telah disetujui.",
            route('bookings.show', $booking),
        );
    }
}
