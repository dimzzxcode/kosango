<?php

namespace App\Contracts;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function method(): PaymentMethod;

    /**
     * Payment instructions shown to the customer before they transfer.
     *
     * @return array<string, mixed>
     */
    public function instructions(Invoice $invoice): array;

    /**
     * Handle an incoming webhook payload and return the affected payment.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): ?Payment;
}
