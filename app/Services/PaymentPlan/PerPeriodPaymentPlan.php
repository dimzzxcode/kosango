<?php

namespace App\Services\PaymentPlan;

use App\Contracts\PaymentPlanInterface;
use App\Enums\PaymentPlan;
use App\Models\Booking;
use App\Services\Pricing\PeriodCalendar;
use Illuminate\Support\Carbon;

class PerPeriodPaymentPlan implements PaymentPlanInterface
{
    public function __construct(private readonly PeriodCalendar $calendar) {}

    public function supports(PaymentPlan $plan): bool
    {
        return $plan === PaymentPlan::PerPeriod;
    }

    /**
     * @return array<int, array{amount: int, due_date: Carbon, allow_partial: bool}>
     */
    public function invoices(Booking $booking): array
    {
        $starts = $this->calendar->periodStarts(
            $booking->period,
            $booking->check_in->copy()->startOfDay(),
            $booking->duration,
        );

        return array_map(function ($start, $index) use ($booking): array {
            $due = $start->copy()->subDay()->startOfDay();

            if ($index === 0 && $due->isPast()) {
                $due = now()->startOfDay();
            }

            return [
                'amount' => $booking->unit_price,
                'due_date' => $due,
                'allow_partial' => false,
            ];
        }, $starts, array_keys($starts));
    }
}
