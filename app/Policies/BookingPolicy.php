<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->ownsBooking($user, $booking)
            || $user->canManageProperty($booking->property);
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $this->ownsBooking($user, $booking)
            || $user->canManageProperty($booking->property);
    }

    public function complete(User $user, Booking $booking): bool
    {
        return $user->canManageProperty($booking->property);
    }

    public function extend(User $user, Booking $booking): bool
    {
        return $this->ownsBooking($user, $booking) && $booking->canBeExtended();
    }

    public function review(User $user, Booking $booking): bool
    {
        return $this->ownsBooking($user, $booking);
    }

    public function manageAsAdmin(User $user, Booking $booking): bool
    {
        return $user->canManageProperty($booking->property);
    }

    private function ownsBooking(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }
}
