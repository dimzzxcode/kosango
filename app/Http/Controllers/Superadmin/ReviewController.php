<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    /**
     * List every review for moderation.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['visibility', 'q']);

        $reviews = Review::query()
            ->with(['property:id,name,slug', 'user:id,name,email', 'booking:id,code'])
            ->when(($filters['visibility'] ?? null) === 'hidden', fn (Builder $query) => $query->where('is_visible', false))
            ->when(($filters['visibility'] ?? null) === 'visible', fn (Builder $query) => $query->where('is_visible', true))
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query
                ->where(fn (Builder $inner) => $inner
                    ->whereHas('property', fn (Builder $propertyQuery) => $propertyQuery->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$term}%"))))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('superadmin/reviews/Index', [
            'reviews' => $reviews,
            'filters' => $filters,
        ]);
    }

    /**
     * Show or hide a review from the public property page.
     */
    public function visibility(Review $review): RedirectResponse
    {
        $review->update(['is_visible' => ! $review->is_visible]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $review->is_visible ? 'Review ditampilkan.' : 'Review disembunyikan.',
        ]);

        return back();
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Review dihapus.']);

        return back();
    }
}
