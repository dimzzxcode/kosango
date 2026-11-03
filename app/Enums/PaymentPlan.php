<?php

namespace App\Enums;

enum PaymentPlan: string
{
    case Full = 'full';
    case PerPeriod = 'per_period';
    case Installment = 'installment';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Lunas Sekaligus',
            self::PerPeriod => 'Bayar per Periode',
            self::Installment => 'Cicilan Tahunan',
        };
    }

    public function allowedFor(BookingPeriod $period): bool
    {
        return in_array($this, $period->allowedPaymentPlans(), true);
    }
}
