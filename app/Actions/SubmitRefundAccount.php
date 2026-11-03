<?php

namespace App\Actions;

use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Models\User;
use App\Services\Notifier;
use App\Services\StatusHistoryRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitRefundAccount
{
    public function __construct(
        private readonly StatusHistoryRecorder $histories,
        private readonly Notifier $notifier,
    ) {}

    /**
     * @param  array{bank_name: string, account_number: string, account_holder: string}  $data
     */
    public function handle(Refund $refund, User $user, array $data): Refund
    {
        return DB::transaction(function () use ($refund, $user, $data): Refund {
            $refund = Refund::query()->whereKey($refund->getKey())->lockForUpdate()->firstOrFail();
            $refund->loadMissing('booking.property');

            if ($refund->booking->user_id !== $user->id) {
                throw ValidationException::withMessages(['refund' => 'Refund bukan milik Anda.']);
            }

            if (! in_array($refund->status, [RefundStatus::AwaitingAccount, RefundStatus::Rejected], true)) {
                throw ValidationException::withMessages(['refund' => 'Rekening refund tidak dapat diubah pada status ini.']);
            }

            $from = $refund->status;

            $refund->update([
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_holder' => $data['account_holder'],
                'status' => RefundStatus::Requested,
                'rejection_reason' => null,
            ]);

            $this->histories->record(
                $refund,
                $from->value,
                RefundStatus::Requested->value,
                $user,
                'Customer mengisi rekening tujuan refund.',
            );

            $this->notifier->toPropertyAdmins(
                $refund->booking->property,
                'Refund siap diproses',
                "Rekening refund untuk booking {$refund->booking->code} telah diisi.",
                route('admin.refunds.index'),
            );

            return $refund->refresh();
        });
    }
}
