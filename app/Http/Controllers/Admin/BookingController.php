<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CancelBooking;
use App\Actions\CompleteBooking;
use App\Enums\BookingStatus;
use App\Enums\RefundInitiator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelBookingRequest;
use App\Http\Requests\CompleteBookingRequest;
use App\Models\BankAccount;
use App\Repositories\BookingRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(private readonly BookingRepository $bookings) {}

    /**
     * Show the bookings of every property the staff member manages.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'q', 'property']);

        $bookings = $this->bookings->queryForStaff($request->user())
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query
                ->where('status', $status))
            ->when($filters['property'] ?? null, fn ($query, string $property) => $query
                ->whereHas('property', fn ($inner) => $inner->where('slug', $property)))
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query
                ->where(function ($inner) use ($term): void {
                    $inner->where('code', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%"));
                }))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/bookings/Index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'statuses' => collect(BookingStatus::cases())->map(fn (BookingStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ]);
    }

    /**
     * Show a booking with every invoice, payment, and refund.
     */
    public function show(Request $request, string $booking): Response
    {
        return Inertia::render('admin/bookings/Show', [
            'booking' => $this->bookings->findForStaff($request->user(), $booking),
            'banks' => BankAccount::query()
                ->where('is_active', true)
                ->orderBy('bank_name')
                ->get(),
        ]);
    }

    /**
     * Mark a confirmed booking as completed.
     */
    public function complete(
        CompleteBookingRequest $request,
        string $booking,
        CompleteBooking $completeBooking,
    ): RedirectResponse {
        $model = $this->bookings->findForStaff($request->user(), $booking);

        $completeBooking->handle($model, $request->user(), $request->validated('note'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Booking ditandai selesai.',
        ]);

        return back();
    }

    /**
     * Cancel a booking on behalf of the property owner.
     */
    public function cancel(
        CancelBookingRequest $request,
        string $booking,
        CancelBooking $cancelBooking,
    ): RedirectResponse {
        $model = $this->bookings->findForStaff($request->user(), $booking);

        $cancelBooking->handle($model, RefundInitiator::Admin, $request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Booking dibatalkan dan refund dicatat.',
        ]);

        return back();
    }
}
