<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;

class PaymentGatewayManager
{
    /**
     * @param  array<string, PaymentGatewayInterface>  $drivers
     */
    public function __construct(private readonly array $drivers) {}

    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name ??= (string) config('booking.payment_driver');

        return $this->drivers[$name]
            ?? throw new \InvalidArgumentException("Unsupported payment driver [{$name}].");
    }
}
