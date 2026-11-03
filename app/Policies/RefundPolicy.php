<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function view(User $user, Refund $refund): bool
    {
        return $refund->booking->user_id === $user->id
            || $user->canManageProperty($refund->booking->property);
    }

    public function submitAccount(User $user, Refund $refund): bool
    {
        return $refund->booking->user_id === $user->id;
    }

    public function process(User $user, Refund $refund): bool
    {
        return $user->canManageProperty($refund->booking->property);
    }

    public function downloadProof(User $user, Refund $refund): bool
    {
        return $refund->booking->user_id === $user->id
            || $user->canManageProperty($refund->booking->property);
    }
}
