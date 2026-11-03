<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\Notifier;
use App\Services\StatusHistoryRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectPayment
{
    public function __construct(
        private readonly StatusHistoryRecorder $histories,
        private readonly Notifier $notifier,
    ) {}

    public function handle(Payment $payment, User $actor, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $actor, $reason): Payment {
            $payment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($payment->status !== PaymentStatus::WaitingVerification) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran ini tidak sedang menunggu verifikasi.',
                ]);
            }

            $invoice = $payment->invoice()->firstOrFail();
            $booking = $invoice->booking;

            if (! $actor->canManageProperty($booking->property)) {
                throw ValidationException::withMessages(['payment' => 'Anda tidak berhak menolak pembayaran ini.']);
            }

            $payment->update([
                'status' => PaymentStatus::Rejected,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'rejection_reason' => $reason,
            ]);

            if ($booking->status === BookingStatus::PendingPayment) {
                $newExpiry = now()->addHours((int) config('booking.payment_expiry_hours'));

                if ($booking->expires_at === null || $newExpiry->greaterThan($booking->expires_at)) {
                    $booking->update(['expires_at' => $newExpiry]);
                }
            }

            $this->histories->record(
                $payment,
                PaymentStatus::WaitingVerification->value,
                PaymentStatus::Rejected->value,
                $actor,
                $reason,
            );

            $this->notifier->toUser(
                $booking->user,
                'Pembayaran ditolak',
                "Bukti pembayaran {$invoice->number} ditolak: {$reason}. Silakan unggah ulang bukti transfer.",
                route('bookings.show', $booking),
            );

            return $payment->load('invoice.booking');
        });
    }
}
