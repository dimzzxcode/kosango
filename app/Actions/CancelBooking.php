<?php

namespace App\Actions;

use App\Contracts\RefundPolicyInterface;
use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundInitiator;
use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\Notifier;
use App\Services\StatusHistoryRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelBooking
{
    public function __construct(
        private readonly StatusHistoryRecorder $histories,
        private readonly Notifier $notifier,
    ) {}

    /**
     * @param  array{reason?: string|null, bank_name?: string|null, account_number?: string|null, account_holder?: string|null}  $data
     */
    public function handle(Booking $booking, RefundInitiator $initiator, ?User $actor, array $data = []): Booking
    {
        return DB::transaction(function () use ($booking, $initiator, $actor, $data): Booking {
            $booking = Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
            $booking->loadMissing(['invoices.payments', 'refund']);

            if (! $booking->status->canTransitionTo(BookingStatus::Cancelled)) {
                throw ValidationException::withMessages([
                    'booking' => 'Booking ini tidak dapat dibatalkan.',
                ]);
            }

            if ($booking->status === BookingStatus::PendingPayment && $booking->hasWaitingVerification()) {
                throw ValidationException::withMessages([
                    'booking' => 'Booking tidak dapat dibatalkan selama ada pembayaran yang menunggu verifikasi.',
                ]);
            }

            if ($initiator === RefundInitiator::Customer && $booking->user_id !== $actor?->id) {
                throw ValidationException::withMessages(['booking' => 'Booking bukan milik Anda.']);
            }

            if ($initiator === RefundInitiator::Admin && ! $actor?->canManageProperty($booking->property)) {
                throw ValidationException::withMessages(['booking' => 'Anda tidak berhak membatalkan booking ini.']);
            }

            if ($booking->refund()->whereIn('status', [
                RefundStatus::AwaitingAccount->value,
                RefundStatus::Requested->value,
            ])->exists()) {
                throw ValidationException::withMessages([
                    'booking' => 'Booking ini sudah memiliki refund yang sedang diproses.',
                ]);
            }

            /** @var RefundPolicyInterface $policy */
            $policy = app(RefundPolicyInterface::class, ['initiator' => $initiator]);
            $decision = $policy->decide($booking);

            $bankName = $data['bank_name'] ?? null;
            $accountNumber = $data['account_number'] ?? null;
            $accountHolder = $data['account_holder'] ?? null;

            if ($initiator === RefundInitiator::Customer && $decision->amount > 0) {
                if (blank($bankName) || blank($accountNumber) || blank($accountHolder)) {
                    throw ValidationException::withMessages([
                        'bank_name' => 'Data rekening tujuan refund wajib diisi.',
                        'account_number' => 'Nomor rekening tujuan refund wajib diisi.',
                        'account_holder' => 'Nama pemilik rekening wajib diisi.',
                    ]);
                }
            }

            $invoiceStatuses = $booking->invoices->map(fn ($invoice) => [
                'id' => $invoice->id,
                'from' => $invoice->status->value,
            ]);

            $booking->invoices()
                ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Partial->value])
                ->update(['status' => InvoiceStatus::Cancelled->value]);

            $booking->invoices->each(function ($invoice) use ($invoiceStatuses, $actor): void {
                $from = $invoiceStatuses->firstWhere('id', $invoice->id)['from'] ?? null;

                $invoice->payments()
                    ->where('status', PaymentStatus::WaitingVerification->value)
                    ->update(['status' => PaymentStatus::Expired->value]);

                if ($from !== InvoiceStatus::Cancelled->value) {
                    $this->histories->record(
                        $invoice,
                        $from,
                        InvoiceStatus::Cancelled->value,
                        $actor,
                        'Tagihan dibatalkan karena booking dibatalkan.',
                    );
                }
            });

            $fromStatus = $booking->status->value;

            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $initiator === RefundInitiator::Admin ? $actor?->id : $booking->user_id,
                'cancel_reason' => $data['reason'] ?? null,
            ]);

            $this->notifier->toPropertyAdmins(
                $booking->property,
                'Booking dibatalkan',
                "Booking {$booking->code} dibatalkan oleh ".($initiator === RefundInitiator::Admin ? 'admin' : 'customer').'.',
                route('admin.bookings.show', $booking),
            );

            if ($decision->amount > 0) {
                $booking->refund()->create([
                    'requested_by' => $actor?->id,
                    'initiated_by' => $initiator,
                    'amount' => $decision->amount,
                    'status' => $initiator === RefundInitiator::Admin
                        && (blank($bankName) || blank($accountNumber) || blank($accountHolder))
                            ? RefundStatus::AwaitingAccount
                            : RefundStatus::Requested,
                    'bank_name' => $bankName,
                    'account_number' => $accountNumber,
                    'account_holder' => $accountHolder,
                    'note' => $decision->reason,
                ]);

                $this->notifier->toPropertyAdmins(
                    $booking->property,
                    'Refund diminta',
                    "Refund untuk booking {$booking->code} sebesar Rp ".number_format($decision->amount, 0, ',', '.').' menunggu proses.',
                    route('admin.refunds.index'),
                );

                if ($initiator === RefundInitiator::Admin) {
                    $this->notifier->toUser(
                        $booking->user,
                        'Booking dibatalkan oleh admin',
                        "Booking {$booking->code} dibatalkan. Silakan isi rekening tujuan refund.",
                        route('bookings.show', $booking),
                    );
                }
            }

            $this->histories->record(
                $booking,
                $fromStatus,
                BookingStatus::Cancelled->value,
                $actor,
                $decision->eligible ? $decision->reason : 'Tanpa refund: '.$decision->reason,
            );

            $this->notifier->toUser(
                $booking->user,
                'Booking dibatalkan',
                "Booking {$booking->code} telah dibatalkan.",
                route('bookings.show', $booking),
            );

            if ($initiator === RefundInitiator::Admin) {
                $this->notifier->toSuperadmins(
                    'Pembatalan oleh admin',
                    "Admin membatalkan booking {$booking->code}.",
                    route('admin.bookings.show', $booking),
                );
            }

            $booking->extensions()
                ->whereIn('status', [BookingStatus::PendingPayment->value, BookingStatus::Confirmed->value])
                ->get()
                ->each(function (Booking $extension) use ($initiator, $actor, $data): void {
                    $this->handle($extension, $initiator, $actor, $data);
                });

            return $booking->refresh()->load([
                'property.city', 'roomType', 'room', 'invoices.payments', 'refund', 'extensions',
            ]);
        });
    }
}
