<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Models\User;
use App\Services\StatusHistoryRecorder;
use Illuminate\Validation\ValidationException;

class ReopenUpload
{
    public function __construct(private readonly StatusHistoryRecorder $histories) {}

    public function handle(Invoice $invoice, User $actor): Invoice
    {
        $invoice->loadMissing('booking.property');

        if (! $actor->canManageProperty($invoice->booking->property)) {
            throw ValidationException::withMessages(['invoice' => 'Anda tidak berhak membuka upload tagihan ini.']);
        }

        $invoice->update(['upload_reset_at' => now()]);

        $this->histories->record(
            $invoice,
            $invoice->status->value,
            $invoice->status->value,
            $actor,
            'Batas upload bukti dibuka kembali oleh admin.',
        );

        return $invoice->refresh();
    }
}
