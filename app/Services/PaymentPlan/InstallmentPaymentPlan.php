<?php

namespace App\Services\PaymentPlan;

use App\Contracts\PaymentPlanInterface;
use App\Enums\PaymentPlan;
use App\Models\Booking;
use Illuminate\Support\Carbon;

class InstallmentPaymentPlan implements PaymentPlanInterface
{
    public function supports(PaymentPlan $plan): bool
    {
        return $plan === PaymentPlan::Installment;
    }

    /**
     * @return array<int, array{amount: int, due_date: Carbon, allow_partial: bool}>
     */
    public function invoices(Booking $booking): array
    {
        return [[
            'amount' => $booking->total_price,
            'due_date' => $booking->check_out->copy()->subDay()->startOfDay(),
            'allow_partial' => true,
        ]];
    }
}
