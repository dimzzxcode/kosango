<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Page $page): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Page $page): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->isSuperadmin();
    }
}
