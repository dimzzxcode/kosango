<?php

namespace App\Services\Refund;

use App\Contracts\RefundPolicyInterface;
use App\DTOs\RefundDecision;
use App\Enums\BookingStatus;
use App\Models\Booking;

class CustomerCancellationRefundPolicy implements RefundPolicyInterface
{
    public function decide(Booking $booking): RefundDecision
    {
        if ($booking->status === BookingStatus::PendingPayment) {
            return RefundDecision::none('Booking belum memiliki pembayaran terverifikasi.');
        }

        if ($booking->status !== BookingStatus::Confirmed) {
            return RefundDecision::none('Booking tidak berada pada status yang dapat direfund.');
        }

        if (! $booking->confirmedWithinRefundWindow()) {
            return RefundDecision::none(
                'Pembatalan melewati batas '.config('booking.refund_window_days').' hari sejak konfirmasi.',
            );
        }

        if (today()->greaterThanOrEqualTo($booking->check_in)) {
            return RefundDecision::none('Pembatalan dilakukan setelah tanggal check-in.');
        }

        $amount = (int) $booking->invoices->sum(
            fn ($invoice) => min($invoice->paid_amount, $invoice->amount),
        );

        if ($amount <= 0) {
            return RefundDecision::none('Tidak ada pokok sewa yang dapat dikembalikan.');
        }

        return new RefundDecision(
            true,
            $amount,
            'Refund penuh pokok sewa karena pembatalan dalam 3 hari sejak konfirmasi dan belum check-in.',
        );
    }
}
