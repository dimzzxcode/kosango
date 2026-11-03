<?php

namespace App\Services\Pricing;

use App\Contracts\PeriodCalculatorInterface;
use App\Enums\BookingPeriod;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PeriodCalendar
{
    public function __construct(private readonly PeriodCalculatorFactory $factory) {}

    public function add(BookingPeriod $period, CarbonInterface $start, int $periods): CarbonInterface
    {
        return $this->factory->make($period)->addPeriods($start, $periods);
    }

    /**
     * @return array<int, CarbonInterface>
     */
    public function periodStarts(BookingPeriod $period, CarbonInterface $start, int $periods): array
    {
        return $this->factory->make($period)->periodStarts($start, $periods);
    }

    public function ensureSupported(BookingPeriod $period): PeriodCalculatorInterface
    {
        try {
            return $this->factory->make($period);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'period' => 'Periode sewa tidak didukung.',
            ]);
        }
    }
}
