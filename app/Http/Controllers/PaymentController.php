<?php

namespace App\Http\Controllers;

use App\Actions\SubmitPaymentProof;
use App\Http\Requests\SubmitPaymentProofRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    /**
     * Record a transfer proof upload for an invoice.
     */
    public function store(
        SubmitPaymentProofRequest $request,
        string $booking,
        Invoice $invoice,
        SubmitPaymentProof $submitPaymentProof,
    ): RedirectResponse {
        abort_unless($invoice->booking->code === $booking, 404);

        $submitPaymentProof->handle(
            $request->user(),
            $invoice->booking,
            $invoice,
            (int) $request->validated('amount'),
            $request->file('proof'),
            $request->validated('bank_account_id'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Bukti transfer diunggah. Menunggu verifikasi admin.',
        ]);

        return back();
    }

    /**
     * Stream a private payment proof to an authorized user.
     */
    public function proof(Request $request, Payment $payment): StreamedResponse
    {
        abort_unless($request->user()->can('downloadProof', $payment), 403);
        abort_unless(filled($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path, 'bukti-'.$payment->id.'.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION));
    }
}
