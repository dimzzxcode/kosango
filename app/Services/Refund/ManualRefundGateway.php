<?php

namespace App\Services\Refund;

use App\Contracts\RefundGatewayInterface;
use App\Models\Refund;
use App\Models\User;

class ManualRefundGateway implements RefundGatewayInterface
{
    public function transfer(Refund $refund, User $actor): Refund
    {
        return $refund;
    }
}
