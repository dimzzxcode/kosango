<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Review $review): bool
    {
        return $review->is_visible || ($user !== null && $user->isSuperadmin());
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function moderate(User $user, Review $review): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->isSuperadmin();
    }
}
