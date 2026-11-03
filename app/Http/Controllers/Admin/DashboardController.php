<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingPeriod;
use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the operational dashboard for assigned properties.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $bookings = $this->scopedBookings($user);
        $invoices = Invoice::query()
            ->whereHas('booking', fn (Builder $query) => $this->scopeBookingQuery($query, $user));

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'activeBookings' => (clone $bookings)->whereIn('status', [
                    BookingStatus::PendingPayment->value,
                    BookingStatus::Confirmed->value,
                ])->count(),
                'pendingVerifications' => (clone $bookings)
                    ->whereHas('invoices.payments', fn (Builder $query) => $query
                        ->where('status', PaymentStatus::WaitingVerification->value))
                    ->count(),
                'overdueInvoices' => (clone $invoices)
                    ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Partial->value])
                    ->where('due_date', '<', today()->toDateString())
                    ->count(),
                'refundsWaiting' => (clone $bookings)
                    ->whereHas('refund', fn (Builder $query) => $query
                        ->whereIn('status', ['awaiting_account', 'requested']))
                    ->count(),
                'paidThisMonth' => (int) (clone $invoices)
                    ->where('status', InvoiceStatus::Paid->value)
                    ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('paid_amount'),
            ],
            'recentBookings' => (clone $bookings)
                ->with(['property.city', 'roomType', 'room', 'user'])
                ->latest()
                ->limit(8)
                ->get(),
            'pendingPayments' => (clone $bookings)
                ->whereHas('invoices.payments', fn (Builder $query) => $query
                    ->where('status', PaymentStatus::WaitingVerification->value))
                ->with([
                    'property.city',
                    'roomType',
                    'invoices' => fn ($query) => $query->whereHas('payments', fn (Builder $inner) => $inner
                        ->where('status', PaymentStatus::WaitingVerification->value)),
                    'invoices.payments',
                ])
                ->latest()
                ->limit(6)
                ->get(),
            'roomSummary' => $this->roomSummary($user),
            'periods' => collect(BookingPeriod::cases())->map(fn (BookingPeriod $period): array => [
                'value' => $period->value,
                'label' => $period->label(),
            ]),
        ]);
    }

    /**
     * @return Builder<Booking>
     */
    private function scopedBookings(User $user): Builder
    {
        $query = Booking::query();

        $this->scopeBookingQuery($query, $user);

        return $query;
    }

    /**
     * @param  Builder<Booking>  $query
     */
    private function scopeBookingQuery(Builder $query, User $user): void
    {
        if ($user->isSuperadmin()) {
            return;
        }

        $query->whereHas('property.admins', fn (Builder $adminQuery) => $adminQuery->whereKey($user->id));
    }

    /**
     * @return array<string, int>
     */
    private function roomSummary(User $user): array
    {
        $rooms = Room::query();

        if (! $user->isSuperadmin()) {
            $rooms->whereHas('property.admins', fn (Builder $query) => $query->whereKey($user->id));
        }

        return [
            'available' => (clone $rooms)->where('status', 'available')->count(),
            'maintenance' => (clone $rooms)->where('status', 'maintenance')->count(),
        ];
    }
}
