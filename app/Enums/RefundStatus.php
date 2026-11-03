<?php

namespace App\Enums;

enum RefundStatus: string
{
    case AwaitingAccount = 'awaiting_account';
    case Requested = 'requested';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingAccount => 'Menunggu Rekening',
            self::Requested => 'Diproses',
            self::Completed => 'Selesai',
            self::Rejected => 'Ditolak',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::AwaitingAccount, self::Requested], true);
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::AwaitingAccount => [self::Requested, self::Rejected],
            self::Requested => [self::Completed, self::Rejected],
            self::Rejected => [self::Requested],
            self::Completed => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
