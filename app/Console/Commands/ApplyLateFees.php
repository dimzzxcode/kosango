<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\BookingSettings;
use App\Services\Notifier;
use App\Services\StatusHistoryRecorder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('invoices:apply-late-fees')]
#[Description('Apply the late fee once an invoice is past its grace period')]
class ApplyLateFees extends Command
{
    public function handle(
        BookingSettings $settings,
        Notifier $notifier,
        StatusHistoryRecorder $histories,
    ): int {
        $applied = 0;

        Invoice::query()
            ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Partial->value])
            ->where('grace_until', '<', today())
            ->whereNull('late_fee_applied_at')
            ->whereHas('booking', fn ($query) => $query
                ->where('status', BookingStatus::Confirmed->value))
            ->with(['booking.user', 'booking.property'])
            ->chunkById(100, function ($invoices) use ($settings, $notifier, $histories, &$applied): void {
                foreach ($invoices as $invoice) {
                    $result = DB::transaction(function () use ($invoice, $settings, $notifier, $histories): bool {
                        $fresh = Invoice::query()->whereKey($invoice->getKey())->lockForUpdate()->first();

                        if ($fresh === null || $fresh->late_fee_applied_at !== null || ! $fresh->status->isOpen()) {
                            return false;
                        }

                        $oldStatus = $fresh->status->value;

                        $fresh->forceFill([
                            'late_fee' => $settings->lateFee(),
                            'late_fee_applied_at' => now(),
                        ])->save();

                        $fresh->recalculateStatus();

                        $histories->record(
                            $fresh,
                            $oldStatus,
                            $fresh->status->value,
                            null,
                            'Denda keterlambatan diterapkan.',
                        );

                        $fresh->load('booking.user', 'booking.property');

                        $notifier->toUser(
                            $fresh->booking->user,
                            'Denda keterlambatan pada '.$fresh->number,
                            'Tagihan '.$fresh->number.' dikenakan denda sebesar Rp '
                                .number_format($settings->lateFee(), 0, ',', '.').' karena melewati masa toleransi.',
                            route('bookings.show', $fresh->booking->code),
                        );

                        return true;
                    });

                    if ($result) {
                        $applied++;
                    }
                }
            });

        $this->info("{$applied} denda keterlambatan diterapkan.");

        return self::SUCCESS;
    }
}
