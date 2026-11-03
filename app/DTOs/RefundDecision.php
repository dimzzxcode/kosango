<?php

namespace App\DTOs;

final class RefundDecision
{
    public function __construct(
        public readonly bool $eligible,
        public readonly int $amount,
        public readonly string $reason,
    ) {}

    public static function none(string $reason): self
    {
        return new self(false, 0, $reason);
    }
}
