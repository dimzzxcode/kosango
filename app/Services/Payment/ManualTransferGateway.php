<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Property;

class ManualTransferGateway implements PaymentGatewayInterface
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::ManualTransfer;
    }

    /**
     * @return array<string, mixed>
     */
    public function instructions(Invoice $invoice): array
    {
        $property = $invoice->booking->property;

        $accounts = $property->bankAccounts()
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            $accounts = Property::query()
                ->whereKey($property->id)
                ->first()
                ?->bankAccounts()
                ->where('is_active', true)
                ->get() ?? collect();
        }

        return [
            'method' => $this->method()->value,
            'label' => 'Transfer manual',
            'accounts' => $accounts->map(fn ($account): array => [
                'id' => $account->id,
                'bank_name' => $account->bank_name,
                'account_number' => $account->account_number,
                'account_holder' => $account->account_holder,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): ?Payment
    {
        return null;
    }
}
