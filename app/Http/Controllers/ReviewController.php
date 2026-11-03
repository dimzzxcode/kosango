<?php

namespace App\Http\Controllers;

use App\Actions\SubmitReview;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Booking $booking, SubmitReview $submitReview): RedirectResponse
    {
        $submitReview->handle($booking, $request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Terima kasih, review Anda sudah tersimpan.',
        ]);

        return back();
    }
}
