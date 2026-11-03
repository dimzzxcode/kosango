<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingSettings;
use App\Services\Notifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('bookings:send-extension-reminders')]
#[Description('Notify customers when their booking can be extended')]
class SendExtensionReminders extends Command
{
    public function handle(BookingSettings $settings, Notifier $notifier): int
    {
        $opensOn = today()->addDays($settings->extensionOpenDaysBeforeEnd());
        $sent = 0;

        Booking::query()
            ->where('status', BookingStatus::Confirmed->value)
            ->whereDate('check_out', $opensOn->toDateString())
            ->whereDoesntHave('extensions', fn ($query) => $query
                ->whereIn('status', [BookingStatus::PendingPayment->value, BookingStatus::Confirmed->value]))
            ->with(['user', 'property', 'roomType'])
            ->chunkById(100, function ($bookings) use ($notifier, $opensOn, &$sent): void {
                foreach ($bookings as $booking) {
                    $cacheKey = "extension-reminder:{$booking->id}:{$opensOn->toDateString()}";

                    if (! Cache::add($cacheKey, true, now()->endOfDay())) {
                        continue;
                    }

                    $notifier->toUser(
                        $booking->user,
                        'Perpanjangan booking '.$booking->code.' sudah dibuka',
                        "Anda dapat memperpanjang booking {$booking->code} di {$booking->property->name} "
                            ."sebelum masa sewa berakhir pada {$booking->check_out->format('d/m/Y')}.",
                        route('bookings.show', $booking->code),
                    );

                    $sent++;
                }
            });

        $this->info("{$sent} pengingat perpanjangan dikirim.");

        return self::SUCCESS;
    }
}
