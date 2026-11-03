<?php

namespace App\Contracts;

use App\Models\Refund;
use App\Models\User;

interface RefundGatewayInterface
{
    /**
     * Transfer a refund manually outside the system, or through a gateway API.
     */
    public function transfer(Refund $refund, User $actor): Refund;
}
