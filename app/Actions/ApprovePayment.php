<?php

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Events\InvoicePaid;
use App\Models\Payment;
use App\Models\User;
use App\Services\Notifier;
use App\Services\StatusHistoryRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovePayment
{
    public function __construct(
        private readonly StatusHistoryRecorder $histories,
        private readonly Notifier $notifier,
    ) {}

    public function handle(Payment $payment, User $actor): Payment
    {
        $result = DB::transaction(function () use ($payment, $actor): Payment {
            $payment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($payment->status !== PaymentStatus::WaitingVerification) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran ini tidak sedang menunggu verifikasi.',
                ]);
            }

            $invoice = $payment->invoice()->lockForUpdate()->firstOrFail();
            $invoice->load('booking');

            if (! $actor->canManageProperty($invoice->booking->property)) {
                throw ValidationException::withMessages(['payment' => 'Anda tidak berhak memverifikasi pembayaran ini.']);
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => $payment->paid_at ?? now(),
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]);

            $oldInvoiceStatus = $invoice->status->value;
            $invoice->forceFill(['paid_amount' => $invoice->paid_amount + $payment->amount])->save();
            $invoice->recalculateStatus();

            $this->histories->record(
                $payment,
                PaymentStatus::WaitingVerification->value,
                PaymentStatus::Paid->value,
                $actor,
                'Pembayaran disetujui.',
            );

            if ($oldInvoiceStatus !== $invoice->status->value) {
                $this->histories->record(
                    $invoice,
                    $oldInvoiceStatus,
                    $invoice->status->value,
                    $actor,
                    'Status tagihan diperbarui setelah pembayaran disetujui.',
                );
            }

            $this->notifier->toUser(
                $invoice->booking->user,
                'Pembayaran disetujui',
                "Pembayaran {$invoice->number} sebesar Rp ".number_format($payment->amount, 0, ',', '.').' telah disetujui.',
                route('bookings.show', $invoice->booking),
            );

            return $payment->load('invoice.booking');
        });

        InvoicePaid::dispatch($result->invoice);

        return $result;
    }
}
