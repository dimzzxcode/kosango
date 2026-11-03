<?php

namespace App\Services\Pricing;

use App\Contracts\PeriodCalculatorInterface;
use App\Enums\BookingPeriod;
use Carbon\CarbonInterface;

class YearlyPeriodCalculator implements PeriodCalculatorInterface
{
    public function supports(BookingPeriod $period): bool
    {
        return $period === BookingPeriod::Yearly;
    }

    public function addPeriods(CarbonInterface $start, int $periods): CarbonInterface
    {
        return $start->copy()->addYearsNoOverflow($periods);
    }

    /**
     * @return array<int, CarbonInterface>
     */
    public function periodStarts(CarbonInterface $start, int $periods): array
    {
        $starts = [];

        for ($index = 0; $index < $periods; $index++) {
            $starts[] = $start->copy()->addYearsNoOverflow($index);
        }

        return $starts;
    }
}
