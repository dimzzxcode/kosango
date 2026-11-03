<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\User;
use App\Notifications\BookingNotification;

class Notifier
{
    public function toUser(User $user, string $subject, string $body, ?string $url = null): void
    {
        $user->notify(new BookingNotification($subject, $body, $url));
    }

    public function toPropertyAdmins(Property $property, string $subject, string $body, ?string $url = null): void
    {
        $admins = $property->admins()->get();

        if ($admins->isEmpty()) {
            $admins = User::query()->where('role', UserRole::Superadmin->value)->get();
        }

        $admins->each->notify(new BookingNotification($subject, $body, $url));
    }

    public function toSuperadmins(string $subject, string $body, ?string $url = null): void
    {
        User::query()
            ->where('role', UserRole::Superadmin->value)
            ->get()
            ->each->notify(new BookingNotification($subject, $body, $url));
    }
}
