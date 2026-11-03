<?php

namespace App\Services\Pricing;

use App\Contracts\PeriodCalculatorInterface;
use App\Enums\BookingPeriod;
use Carbon\CarbonInterface;

class DailyPeriodCalculator implements PeriodCalculatorInterface
{
    public function supports(BookingPeriod $period): bool
    {
        return $period === BookingPeriod::Daily;
    }

    public function addPeriods(CarbonInterface $start, int $periods): CarbonInterface
    {
        return $start->copy()->addDays($periods);
    }

    /**
     * @return array<int, CarbonInterface>
     */
    public function periodStarts(CarbonInterface $start, int $periods): array
    {
        $starts = [];

        for ($index = 0; $index < $periods; $index++) {
            $starts[] = $start->copy()->addDays($index);
        }

        return $starts;
    }
}
