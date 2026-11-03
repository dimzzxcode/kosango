<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\BookingSettings;
use App\Services\Notifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('invoices:send-reminders')]
#[Description('Send due date reminders a few days before an invoice is due')]
class SendInvoiceReminders extends Command
{
    public function handle(BookingSettings $settings, Notifier $notifier): int
    {
        $remindOn = today()->addDays($settings->invoiceReminderDays());
        $sent = 0;

        Invoice::query()
            ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Partial->value])
            ->whereDate('due_date', $remindOn->toDateString())
            ->whereHas('booking', fn ($query) => $query
                ->where('status', BookingStatus::Confirmed->value))
            ->with(['booking.user', 'booking.property'])
            ->chunkById(100, function ($invoices) use ($notifier, $remindOn, &$sent): void {
                foreach ($invoices as $invoice) {
                    $cacheKey = "invoice-reminder:{$invoice->id}:{$remindOn->toDateString()}";

                    if (! Cache::add($cacheKey, true, now()->endOfDay())) {
                        continue;
                    }

                    $booking = $invoice->booking;
                    $amount = number_format($invoice->remaining(), 0, ',', '.');

                    $notifier->toUser(
                        $booking->user,
                        'Pengingat tagihan '.$invoice->number,
                        "Tagihan {$invoice->number} sebesar Rp {$amount} jatuh tempo pada "
                            .$remindOn->format('d/m/Y').'.',
                        route('bookings.show', $booking->code),
                    );

                    $sent++;
                }
            });

        $this->info("{$sent} pengingat tagihan dikirim.");

        return self::SUCCESS;
    }
}
