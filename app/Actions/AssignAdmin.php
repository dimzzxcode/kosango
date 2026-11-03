<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AssignAdmin
{
    public function handle(User $admin, Property $property, User $actor): void
    {
        if (! $actor->isSuperadmin()) {
            throw ValidationException::withMessages(['admin' => 'Hanya superadmin yang dapat menugaskan admin.']);
        }

        if ($admin->role !== UserRole::Admin) {
            throw ValidationException::withMessages(['admin' => 'User yang ditugaskan harus memiliki role admin.']);
        }

        $property->admins()->syncWithoutDetaching([$admin->id]);
    }
}
