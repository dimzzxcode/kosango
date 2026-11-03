<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApprovePayment;
use App\Actions\RejectPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyPaymentRequest;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Approve or reject a customer's transfer proof.
     */
    public function verify(
        VerifyPaymentRequest $request,
        Payment $payment,
        ApprovePayment $approvePayment,
        RejectPayment $rejectPayment,
    ): RedirectResponse {
        $data = $request->validated();

        if ($data['action'] === 'approve') {
            $approvePayment->handle($payment, $request->user());

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Pembayaran disetujui.',
            ]);
        } else {
            $rejectPayment->handle($payment, $request->user(), (string) $data['reason']);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Pembayaran ditolak. Customer dapat mengunggah ulang.',
            ]);
        }

        return back();
    }
}
