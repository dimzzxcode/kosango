<?php

namespace App\Services\PaymentPlan;

use App\Contracts\PaymentPlanInterface;
use App\Enums\PaymentPlan;

class PaymentPlanFactory
{
    /**
     * @param  array<int, PaymentPlanInterface>  $plans
     */
    public function __construct(private readonly array $plans) {}

    public function make(PaymentPlan $plan): PaymentPlanInterface
    {
        foreach ($this->plans as $candidate) {
            if ($candidate->supports($plan)) {
                return $candidate;
            }
        }

        throw new \InvalidArgumentException("Unsupported payment plan [{$plan->value}].");
    }
}
