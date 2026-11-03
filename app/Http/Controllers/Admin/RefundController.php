<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ProcessRefund;
use App\Actions\RejectRefund;
use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectRefundRequest;
use App\Http\Requests\ProcessRefundRequest;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefundController extends Controller
{
    /**
     * List refunds for the staff member's properties.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['status']);

        $refunds = Refund::query()
            ->with(['booking.property.city', 'booking.user', 'requester', 'processor'])
            ->when(! $user->isSuperadmin(), fn (Builder $query) => $query
                ->whereHas('booking.property.admins', fn (Builder $adminQuery) => $adminQuery->whereKey($user->id)))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query
                ->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/refunds/Index', [
            'refunds' => $refunds,
            'filters' => $filters,
            'statuses' => collect(RefundStatus::cases())->map(fn (RefundStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ]);
    }

    /**
     * Upload the transfer proof and close a refund.
     */
    public function process(
        ProcessRefundRequest $request,
        Refund $refund,
        ProcessRefund $processRefund,
    ): RedirectResponse {
        $processRefund->handle($refund, $request->user(), $request->file('proof'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Refund ditandai selesai.',
        ]);

        return back();
    }

    /**
     * Reject a refund, asking the customer to fix their bank details.
     */
    public function reject(
        RejectRefundRequest $request,
        Refund $refund,
        RejectRefund $rejectRefund,
    ): RedirectResponse {
        $rejectRefund->handle($refund, $request->user(), $request->validated('reason'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Refund ditolak. Customer diminta memperbaiki data rekening.',
        ]);

        return back();
    }
}
