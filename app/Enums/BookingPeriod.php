<?php

namespace App\Enums;

enum BookingPeriod: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Harian',
            self::Monthly => 'Bulanan',
            self::Yearly => 'Tahunan',
        };
    }

    public function unitLabel(): string
    {
        return match ($this) {
            self::Daily => 'hari',
            self::Monthly => 'bulan',
            self::Yearly => 'tahun',
        };
    }

    /**
     * Default maximum duration when a rate does not override it.
     */
    public function defaultMaxDuration(): int
    {
        return match ($this) {
            self::Daily => 30,
            self::Monthly => 24,
            self::Yearly => 5,
        };
    }

    /**
     * @return array<int, PaymentPlan>
     */
    public function allowedPaymentPlans(): array
    {
        return match ($this) {
            self::Daily => [PaymentPlan::Full, PaymentPlan::PerPeriod],
            self::Monthly => [PaymentPlan::Full, PaymentPlan::PerPeriod],
            self::Yearly => [PaymentPlan::Full, PaymentPlan::Installment],
        };
    }
}
