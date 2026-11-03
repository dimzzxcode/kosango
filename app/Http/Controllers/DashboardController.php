<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Refund;
use App\Repositories\BookingRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly BookingRepository $bookings) {}

    /**
     * Show the signed-in guest's overview.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->role->isStaff()) {
            return to_route('admin.dashboard');
        }

        $bookings = $this->bookings->forCustomer($user);

        $bookingIds = $bookings->pluck('id');

        $pendingInvoices = Invoice::query()
            ->whereIn('booking_id', $bookingIds)
            ->whereIn('status', ['unpaid', 'partial'])
            ->with(['booking.property', 'booking.roomType'])
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        $upcomingBooking = $bookings
            ->filter(fn (Booking $booking) => $booking->status->isActive())
            ->sortBy('check_in')
            ->first();

        return Inertia::render('Dashboard', [
            'upcomingBooking' => $upcomingBooking,
            'recentBookings' => $bookings->take(5),
            'pendingInvoices' => $pendingInvoices,
            'refunds' => Refund::query()
                ->whereIn('booking_id', $bookingIds)
                ->with('booking.property')
                ->latest()
                ->limit(3)
                ->get(),
            'notifications' => $user->notifications()->latest()->limit(5)->get(),
            'stats' => [
                'activeBookings' => $bookings->filter(fn (Booking $booking) => $booking->status->isActive())->count(),
                'totalPaid' => (int) $bookings->sum(fn (Booking $booking) => $booking->totalPaid()),
                'outstanding' => (int) $bookings->flatMap->invoices
                    ->filter(fn (Invoice $invoice) => $invoice->status->isOpen())
                    ->sum(fn (Invoice $invoice) => $invoice->remaining()),
                'lateFees' => (int) $bookings->flatMap->invoices->sum('late_fee'),
                'totalBookings' => $bookings->filter(
                    fn (Booking $booking) => $booking->status !== BookingStatus::Cancelled,
                )->count(),
            ],
        ]);
    }
}
