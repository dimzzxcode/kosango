<?php

namespace App\Actions;

use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Models\User;
use App\Services\Notifier;
use App\Services\StatusHistoryRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectRefund
{
    public function __construct(
        private readonly StatusHistoryRecorder $histories,
        private readonly Notifier $notifier,
    ) {}

    public function handle(Refund $refund, User $actor, string $reason): Refund
    {
        return DB::transaction(function () use ($refund, $actor, $reason): Refund {
            $refund = Refund::query()->whereKey($refund->getKey())->lockForUpdate()->firstOrFail();
            $refund->loadMissing('booking.property');

            if (! $actor->canManageProperty($refund->booking->property)) {
                throw ValidationException::withMessages(['refund' => 'Anda tidak berhak menolak refund ini.']);
            }

            if (! in_array($refund->status, [RefundStatus::AwaitingAccount, RefundStatus::Requested], true)) {
                throw ValidationException::withMessages(['refund' => 'Refund ini tidak dapat ditolak.']);
            }

            $from = $refund->status;

            $refund->update([
                'status' => RefundStatus::Rejected,
                'rejection_reason' => $reason,
            ]);

            $this->histories->record(
                $refund,
                $from->value,
                RefundStatus::Rejected->value,
                $actor,
                $reason,
            );

            $this->notifier->toUser(
                $refund->booking->user,
                'Refund ditolak',
                "Refund booking {$refund->booking->code} ditolak: {$reason}. Perbaiki data rekening untuk mengajukan ulang.",
                route('bookings.show', $refund->booking),
            );

            return $refund->refresh();
        });
    }
}
