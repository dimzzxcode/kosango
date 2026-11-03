<?php

namespace App\Services\Pricing;

use App\Contracts\PeriodCalculatorInterface;
use App\Enums\BookingPeriod;

class PeriodCalculatorFactory
{
    /**
     * @param  array<int, PeriodCalculatorInterface>  $calculators
     */
    public function __construct(private readonly array $calculators) {}

    public function make(BookingPeriod $period): PeriodCalculatorInterface
    {
        foreach ($this->calculators as $calculator) {
            if ($calculator->supports($period)) {
                return $calculator;
            }
        }

        throw new \InvalidArgumentException("Unsupported booking period [{$period->value}].");
    }
}
