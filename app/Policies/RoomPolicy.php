<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isStaff();
    }

    public function view(User $user, Room $room): bool
    {
        return $user->canManageProperty($room->property);
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Room $room): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->isSuperadmin();
    }

    public function changeStatus(User $user, Room $room): bool
    {
        return $user->canManageProperty($room->property);
    }
}
