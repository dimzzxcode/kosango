<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $invoice->booking->user_id === $user->id
            || $user->canManageProperty($invoice->booking->property);
    }

    public function uploadProof(User $user, Invoice $invoice): bool
    {
        return $invoice->booking->user_id === $user->id;
    }

    public function reopenUpload(User $user, Invoice $invoice): bool
    {
        return $user->canManageProperty($invoice->booking->property);
    }
}
