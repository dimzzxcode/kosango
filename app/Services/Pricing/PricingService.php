<?php

namespace App\Services\Pricing;

use App\DTOs\Quote;
use App\Enums\BookingPeriod;
use App\Enums\PaymentPlan;
use App\Models\RoomType;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function __construct(private readonly PeriodCalculatorFactory $periodCalculators) {}

    public function quote(
        RoomType $roomType,
        BookingPeriod $period,
        int $duration,
        PaymentPlan $plan,
        CarbonInterface $checkIn,
    ): Quote {
        if (! $plan->allowedFor($period)) {
            throw ValidationException::withMessages([
                'payment_plan' => 'Cara bayar tidak tersedia untuk periode sewa ini.',
            ]);
        }

        $rate = $roomType->activePriceFor($period);

        if ($rate === null) {
            throw ValidationException::withMessages([
                'period' => 'Tipe kamar ini belum memiliki tarif aktif untuk periode yang dipilih.',
            ]);
        }

        if ($duration < $rate->min_duration || $duration > $rate->max_duration) {
            throw ValidationException::withMessages([
                'duration' => "Durasi harus antara {$rate->min_duration} dan {$rate->max_duration} {$period->unitLabel()}.",
            ]);
        }

        $checkOut = $this->periodCalculators
            ->make($period)
            ->addPeriods($checkIn, $duration);

        return new Quote(
            rate: $rate,
            duration: $duration,
            total: $rate->price * $duration,
            checkIn: $checkIn->copy(),
            checkOut: $checkOut,
        );
    }
}
