<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case ManualTransfer = 'manual_transfer';
    case Midtrans = 'midtrans';

    public function label(): string
    {
        return match ($this) {
            self::ManualTransfer => 'Transfer Manual',
            self::Midtrans => 'Midtrans',
        };
    }
}
