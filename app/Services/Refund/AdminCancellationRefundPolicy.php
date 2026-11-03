<?php

namespace App\Services\Refund;

use App\Contracts\RefundPolicyInterface;
use App\DTOs\RefundDecision;
use App\Models\Booking;

class AdminCancellationRefundPolicy implements RefundPolicyInterface
{
    public function decide(Booking $booking): RefundDecision
    {
        $amount = (int) $booking->invoices->sum('paid_amount');

        if ($amount <= 0) {
            return RefundDecision::none('Tidak ada pembayaran terverifikasi untuk direfund.');
        }

        return new RefundDecision(
            true,
            $amount,
            'Refund penuh seluruh pembayaran terverifikasi termasuk denda yang sudah dibayar.',
        );
    }
}
