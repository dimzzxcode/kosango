<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Admin => 'Admin Properti',
            self::Customer => 'Customer',
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::Superadmin, self::Admin], true);
    }
}
