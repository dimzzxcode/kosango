<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ReopenUpload;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * List invoices, highlighting those that are past due.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['scope', 'status', 'q']);

        $invoices = Invoice::query()
            ->with(['booking.property.city', 'booking.roomType', 'booking.user', 'payments'])
            ->when(! $user->isSuperadmin(), fn (Builder $query) => $query
                ->whereHas('booking.property.admins', fn (Builder $adminQuery) => $adminQuery->whereKey($user->id)))
            ->when(($filters['scope'] ?? null) === 'overdue', fn (Builder $query) => $query
                ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Partial->value])
                ->where('due_date', '<', today()->toDateString()))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query
                ->where('status', $status))
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query
                ->where(function (Builder $inner) use ($term): void {
                    $inner->where('number', 'like', "%{$term}%")
                        ->orWhereHas('booking', fn (Builder $bookingQuery) => $bookingQuery
                            ->where('code', 'like', "%{$term}%"));
                }))
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/invoices/Index', [
            'invoices' => $invoices,
            'filters' => $filters,
            'statuses' => collect(InvoiceStatus::cases())->map(fn (InvoiceStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ]);
    }

    /**
     * Reopen the evidence upload limit of an invoice.
     */
    public function reopenUpload(
        Request $request,
        Invoice $invoice,
        ReopenUpload $reopenUpload,
    ): RedirectResponse {
        abort_unless($request->user()->can('reopenUpload', $invoice), 403);

        $reopenUpload->handle($invoice, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Batas upload bukti dibuka kembali.',
        ]);

        return back();
    }
}
