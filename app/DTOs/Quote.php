<?php

namespace App\DTOs;

use App\Models\RoomTypePrice;
use Carbon\CarbonInterface;

final class Quote
{
    public function __construct(
        public readonly RoomTypePrice $rate,
        public readonly int $duration,
        public readonly int $total,
        public readonly CarbonInterface $checkIn,
        public readonly CarbonInterface $checkOut,
    ) {}

    public function unitPrice(): int
    {
        return $this->rate->price;
    }

    public function periodLabel(): string
    {
        return $this->rate->period->unitLabel();
    }
}
