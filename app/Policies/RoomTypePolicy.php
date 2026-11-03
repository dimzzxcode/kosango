<?php

namespace App\Policies;

use App\Models\RoomType;
use App\Models\User;

class RoomTypePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, RoomType $roomType): bool
    {
        return $roomType->property->isPublished()
            || ($user !== null && $user->canManageProperty($roomType->property));
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, RoomType $roomType): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, RoomType $roomType): bool
    {
        return $user->isSuperadmin();
    }
}
