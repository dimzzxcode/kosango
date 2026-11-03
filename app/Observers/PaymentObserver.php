<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\ImageStorageService;

class PaymentObserver
{
    public function __construct(private readonly ImageStorageService $images) {}

    /**
     * Remove the private transfer proof when the payment is force deleted.
     */
    public function forceDeleting(Payment $payment): void
    {
        $this->images->delete($payment->proof_path, 'local');
    }
}
