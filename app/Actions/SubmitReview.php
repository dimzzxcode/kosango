<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubmitReview
{
    /**
     * @param  array{rating: int, comment?: string|null}  $data
     */
    public function handle(Booking $booking, User $user, array $data): Review
    {
        if ($booking->user_id !== $user->id) {
            throw ValidationException::withMessages(['booking' => 'Booking bukan milik Anda.']);
        }

        if ($booking->status !== BookingStatus::Completed) {
            throw ValidationException::withMessages(['booking' => 'Review hanya dapat ditulis setelah booking selesai.']);
        }

        if ($this->hasCompletedExtension($booking)) {
            throw ValidationException::withMessages([
                'booking' => 'Review hanya dapat ditulis pada booking terakhir dalam rantai perpanjangan.',
            ]);
        }

        if ($booking->review()->exists()) {
            throw ValidationException::withMessages(['booking' => 'Booking ini sudah memiliki review.']);
        }

        return $booking->review()->create([
            'property_id' => $booking->property_id,
            'user_id' => $user->id,
            'rating' => (int) $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_visible' => true,
        ]);
    }

    /**
     * A booking in an extension chain can only be reviewed when no later
     * booking of that chain has already been completed.
     */
    private function hasCompletedExtension(Booking $booking): bool
    {
        foreach ($booking->extensions()->get() as $extension) {
            if ($extension->status === BookingStatus::Completed) {
                return true;
            }

            if ($this->hasCompletedExtension($extension)) {
                return true;
            }
        }

        return false;
    }
}
