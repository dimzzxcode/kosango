<?php

namespace App\Contracts;

use App\Enums\PaymentPlan;
use App\Models\Booking;
use Illuminate\Support\Carbon;

interface PaymentPlanInterface
{
    public function supports(PaymentPlan $plan): bool;

    /**
     * @return array<int, array{amount: int, due_date: Carbon, allow_partial: bool}>
     */
    public function invoices(Booking $booking): array;
}
