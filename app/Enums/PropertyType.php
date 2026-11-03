<?php

namespace App\Enums;

enum PropertyType: string
{
    case Hotel = 'hotel';
    case Kos = 'kos';

    public function label(): string
    {
        return match ($this) {
            self::Hotel => 'Hotel',
            self::Kos => 'Kos',
        };
    }
}
