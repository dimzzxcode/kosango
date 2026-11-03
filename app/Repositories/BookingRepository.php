<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BookingRepository
{
    /**
     * @return Collection<int, Booking>
     */
    public function forCustomer(User $user): Collection
    {
        return Booking::query()
            ->where('user_id', $user->id)
            ->with(['property.city', 'roomType', 'room', 'invoices.payments', 'refund', 'review'])
            ->latest()
            ->get();
    }

    public function queryForStaff(User $user): Builder
    {
        $query = Booking::query()
            ->with(['property.city', 'roomType', 'room', 'user', 'invoices', 'refund']);

        if (! $user->isSuperadmin()) {
            $query->whereHas(
                'property.admins',
                fn (Builder $q) => $q->whereKey($user->id),
            );
        }

        return $query->latest();
    }

    public function findForCustomer(User $user, string $code): Booking
    {
        return Booking::query()
            ->where('user_id', $user->id)
            ->where('code', $code)
            ->with([
                'property.city',
                'property.bankAccounts',
                'roomType',
                'room',
                'invoices.payments.bankAccount',
                'refund',
                'review',
                'extensions',
            ])
            ->firstOrFail();
    }

    public function findForStaff(User $user, string $code): Booking
    {
        $booking = Booking::query()
            ->where('code', $code)
            ->with([
                'property.city',
                'property.bankAccounts',
                'roomType',
                'room',
                'user',
                'invoices.payments.verifier',
                'refund.requester',
                'review',
                'extensions',
                'statusHistories.actor',
            ])
            ->firstOrFail();

        abort_unless($user->canManageProperty($booking->property), 403);

        return $booking;
    }
}
