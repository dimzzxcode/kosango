<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $payment->invoice->booking->user_id === $user->id
            || $user->canManageProperty($payment->invoice->booking->property);
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->canManageProperty($payment->invoice->booking->property);
    }

    public function downloadProof(User $user, Payment $payment): bool
    {
        return $payment->invoice->booking->user_id === $user->id
            || $user->canManageProperty($payment->invoice->booking->property);
    }
}
