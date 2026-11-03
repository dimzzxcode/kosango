<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\Notifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('invoices:notify-overdue')]
#[Description('Notify customers and staff about invoices past their due date')]
class NotifyOverdueInvoices extends Command
{
    public function handle(Notifier $notifier): int
    {
        $notified = 0;

        Invoice::query()
            ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Partial->value])
            ->where('due_date', '<', today())
            ->whereHas('booking', fn ($query) => $query
                ->where('status', BookingStatus::Confirmed->value))
            ->with(['booking.user', 'booking.property'])
            ->chunkById(100, function ($invoices) use ($notifier, &$notified): void {
                foreach ($invoices as $invoice) {
                    $cacheKey = "invoice-overdue:{$invoice->id}:".today()->toDateString();

                    if (! Cache::add($cacheKey, true, now()->endOfDay())) {
                        continue;
                    }

                    $booking = $invoice->booking;
                    $amount = number_format($invoice->remaining(), 0, ',', '.');

                    $notifier->toUser(
                        $booking->user,
                        'Tagihan '.$invoice->number.' sudah jatuh tempo',
                        "Tagihan {$invoice->number} sebesar Rp {$amount} sudah melewati jatuh tempo.",
                        route('bookings.show', $booking->code),
                    );

                    $notifier->toPropertyAdmins(
                        $booking->property,
                        'Tagihan terlambat: '.$invoice->number,
                        "Tagihan {$invoice->number} pada booking {$booking->code} melewati jatuh tempo.",
                        route('admin.invoices.index', ['q' => $invoice->number]),
                    );

                    $notified++;
                }
            });

        $this->info("{$notified} notifikasi tagihan terlambat dikirim.");

        return self::SUCCESS;
    }
}
