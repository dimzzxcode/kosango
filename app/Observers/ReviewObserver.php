<?php

namespace App\Observers;

use App\Models\Review;

class ReviewObserver
{
    public function saved(Review $review): void
    {
        $this->refreshProperty($review);
    }

    public function deleted(Review $review): void
    {
        $this->refreshProperty($review);
    }

    public function restored(Review $review): void
    {
        $this->refreshProperty($review);
    }

    private function refreshProperty(Review $review): void
    {
        $property = $review->property;

        if ($property === null) {
            return;
        }

        $visible = $property->reviews()->where('is_visible', true);

        $property->forceFill([
            'rating_avg' => round((float) (clone $visible)->avg('rating'), 2),
            'reviews_count' => (clone $visible)->count(),
        ])->save();
    }
}
