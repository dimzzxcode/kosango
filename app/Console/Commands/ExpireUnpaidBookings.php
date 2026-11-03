<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Services\Notifier;
use App\Services\StatusHistoryRecorder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('bookings:expire-unpaid')]
#[Description('Expire bookings that missed their first payment deadline')]
class ExpireUnpaidBookings extends Command
{
    public function handle(StatusHistoryRecorder $histories, Notifier $notifier): int
    {
        $expired = 0;

        Booking::query()
            ->where('status', BookingStatus::PendingPayment->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereDoesntHave('invoices.payments', fn ($query) => $query
                ->where('status', PaymentStatus::WaitingVerification->value))
            ->with('user')
            ->chunkById(100, function ($bookings) use ($histories, $notifier, &$expired): void {
                foreach ($bookings as $booking) {
                    DB::transaction(function () use ($booking, $histories, $notifier): void {
                        $booking->update(['status' => BookingStatus::Expired]);

                        $histories->record(
                            $booking,
                            BookingStatus::PendingPayment->value,
                            BookingStatus::Expired->value,
                            null,
                            'Kedaluwarsa karena melewati batas pembayaran pertama.',
                        );

                        $booking->invoices()->each(function ($invoice): void {
                            $invoice->payments()
                                ->where('status', PaymentStatus::Pending->value)
                                ->update(['status' => PaymentStatus::Expired->value]);
                        });

                        $notifier->toUser(
                            $booking->user,
                            'Booking '.$booking->code.' kedaluwarsa',
                            "Booking {$booking->code} kedaluwarsa karena pembayaran pertama tidak diselesaikan tepat waktu.",
                            route('bookings.show', $booking),
                        );
                    });

                    $expired++;
                }
            });

        $this->info("{$expired} booking kedaluwarsa.");

        return self::SUCCESS;
    }
}
