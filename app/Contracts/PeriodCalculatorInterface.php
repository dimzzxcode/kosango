<?php

namespace App\Contracts;

use App\Enums\BookingPeriod;
use Carbon\CarbonInterface;

interface PeriodCalculatorInterface
{
    public function supports(BookingPeriod $period): bool;

    public function addPeriods(CarbonInterface $start, int $periods): CarbonInterface;

    /**
     * @return array<int, CarbonInterface> Start date of each period.
     */
    public function periodStarts(CarbonInterface $start, int $periods): array;
}
