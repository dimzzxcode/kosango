<?php

namespace App\Http\Controllers;

use App\Actions\SubmitRefundAccount;
use App\Http\Requests\SubmitRefundAccountRequest;
use App\Models\Refund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RefundController extends Controller
{
    /**
     * Let a customer submit or correct refund bank details.
     */
    public function submitAccount(
        SubmitRefundAccountRequest $request,
        Refund $refund,
        SubmitRefundAccount $submitRefundAccount,
    ): RedirectResponse {
        $submitRefundAccount->handle($refund, $request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Data rekening refund tersimpan.',
        ]);

        return back();
    }

    /**
     * Stream a private refund proof to an authorized user.
     */
    public function proof(Request $request, Refund $refund): StreamedResponse
    {
        abort_unless($request->user()->can('downloadProof', $refund), 403);
        abort_unless(filled($refund->proof_path), 404);

        return Storage::disk('local')->download($refund->proof_path, 'refund-'.$refund->id.'.'.pathinfo($refund->proof_path, PATHINFO_EXTENSION));
    }
}
