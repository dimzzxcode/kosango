<?php

namespace App\Services\PaymentPlan;

use App\Contracts\PaymentPlanInterface;
use App\Enums\PaymentPlan;
use App\Models\Booking;
use Illuminate\Support\Carbon;

class FullPaymentPlan implements PaymentPlanInterface
{
    public function supports(PaymentPlan $plan): bool
    {
        return $plan === PaymentPlan::Full;
    }

    /**
     * @return array<int, array{amount: int, due_date: Carbon, allow_partial: bool}>
     */
    public function invoices(Booking $booking): array
    {
        $due = $booking->check_in->copy()->subDay()->startOfDay();

        if ($due->isPast()) {
            $due = now()->startOfDay();
        }

        return [[
            'amount' => $booking->total_price,
            'due_date' => $due,
            'allow_partial' => false,
        ]];
    }
}
