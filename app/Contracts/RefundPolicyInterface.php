<?php

namespace App\Contracts;

use App\DTOs\RefundDecision;
use App\Models\Booking;

interface RefundPolicyInterface
{
    public function decide(Booking $booking): RefundDecision;
}
