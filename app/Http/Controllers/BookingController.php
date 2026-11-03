<?php

namespace App\Http\Controllers;

use App\Actions\CancelBooking;
use App\Actions\CreateBooking;
use App\Actions\ExtendBooking;
use App\Contracts\RefundPolicyInterface;
use App\Enums\BookingStatus;
use App\Enums\RefundInitiator;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\ExtendBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\Page;
use App\Repositories\BookingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(private readonly BookingRepository $bookings) {}

    /**
     * Show the guest's bookings.
     */
    public function index(Request $request): Response
    {
        $bookings = $this->bookings->forCustomer($request->user());

        return Inertia::render('bookings/Index', [
            'bookings' => $bookings,
            'summary' => [
                'active' => $bookings->filter(fn (Booking $booking) => $booking->status->isActive())->count(),
                'upcoming' => $bookings->filter(fn (Booking $booking) => $booking->check_in->isFuture())->count(),
                'completed' => $bookings->where('status', BookingStatus::Completed)->count(),
                'cancelled' => $bookings->where('status', BookingStatus::Cancelled)->count(),
            ],
        ]);
    }

    /**
     * Create a booking and its payment schedule.
     */
    public function store(StoreBookingRequest $request, CreateBooking $createBooking): RedirectResponse
    {
        $booking = $createBooking->handle($request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Booking berhasil dibuat. Silakan selesaikan pembayaran pertama.',
        ]);

        return to_route('bookings.show', $booking->code);
    }

    /**
     * Show a booking, its invoices, payments, and refund.
     */
    public function show(Request $request, string $booking): Response
    {
        return Inertia::render('bookings/Show', [
            'booking' => $this->bookings->findForCustomer($request->user(), $booking),
            'pages' => Page::query()->get(['slug', 'title']),
            'banks' => BankAccount::query()
                ->where('is_active', true)
                ->orderBy('bank_name')
                ->get(),
        ]);
    }

    /**
     * Preview refund eligibility before a customer cancels.
     */
    public function refundPreview(Request $request, string $booking): JsonResponse
    {
        $bookingModel = $this->bookings->findForCustomer($request->user(), $booking);

        /** @var RefundPolicyInterface $policy */
        $policy = app(RefundPolicyInterface::class, ['initiator' => RefundInitiator::Customer]);
        $decision = $policy->decide($bookingModel);

        return response()->json([
            'eligible' => $decision->eligible,
            'amount' => $decision->amount,
            'reason' => $decision->reason,
        ]);
    }

    /**
     * Cancel a booking and create a refund when eligible.
     */
    public function cancel(
        CancelBookingRequest $request,
        string $booking,
        CancelBooking $cancelBooking,
    ): RedirectResponse {
        $bookingModel = $this->bookings->findForCustomer($request->user(), $booking);

        $cancelBooking->handle($bookingModel, RefundInitiator::Customer, $request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Booking dibatalkan. Jika memenuhi syarat, refund akan diproses admin.',
        ]);

        return to_route('bookings.show', $bookingModel->code);
    }

    /**
     * Extend a booking into a new linked booking.
     */
    public function extend(
        ExtendBookingRequest $request,
        string $booking,
        ExtendBooking $extendBooking,
    ): RedirectResponse {
        $bookingModel = $this->bookings->findForCustomer($request->user(), $booking);

        $extension = $extendBooking->handle($bookingModel, $request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Perpanjangan dibuat. Silakan selesaikan pembayaran.',
        ]);

        return to_route('bookings.show', $extension->code);
    }
}
