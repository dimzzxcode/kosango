<?php

namespace App\Policies;

use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Property $property): bool
    {
        if ($property->status === PropertyStatus::Published) {
            return true;
        }

        return $user !== null && $user->canManageProperty($property);
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Property $property): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->isSuperadmin();
    }

    public function publish(User $user, Property $property): bool
    {
        return $user->isSuperadmin();
    }
}
