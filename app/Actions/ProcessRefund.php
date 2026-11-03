<?php

namespace App\Actions;

use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Models\User;
use App\Services\ImageStorageService;
use App\Services\Notifier;
use App\Services\StatusHistoryRecorder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessRefund
{
    public function __construct(
        private readonly ImageStorageService $images,
        private readonly StatusHistoryRecorder $histories,
        private readonly Notifier $notifier,
    ) {}

    public function handle(Refund $refund, User $actor, UploadedFile $proof): Refund
    {
        return DB::transaction(function () use ($refund, $actor, $proof): Refund {
            $refund = Refund::query()->whereKey($refund->getKey())->lockForUpdate()->firstOrFail();
            $refund->loadMissing('booking.property');

            if (! $actor->canManageProperty($refund->booking->property)) {
                throw ValidationException::withMessages(['refund' => 'Anda tidak berhak memproses refund ini.']);
            }

            if ($refund->status !== RefundStatus::Requested) {
                throw ValidationException::withMessages([
                    'refund' => 'Refund hanya dapat diselesaikan setelah rekening tujuan diisi.',
                ]);
            }

            $path = $this->images->storePrivate($proof, 'refund-proofs');

            $refund->update([
                'status' => RefundStatus::Completed,
                'proof_path' => $path,
                'processed_by' => $actor->id,
                'processed_at' => now(),
            ]);

            $this->histories->record(
                $refund,
                RefundStatus::Requested->value,
                RefundStatus::Completed->value,
                $actor,
                'Refund ditransfer manual dan bukti diunggah.',
            );

            $this->notifier->toUser(
                $refund->booking->user,
                'Refund selesai',
                "Refund booking {$refund->booking->code} sebesar Rp ".number_format($refund->amount, 0, ',', '.').' telah dikirim.',
                route('bookings.show', $refund->booking),
            );

            return $refund->refresh();
        });
    }
}
