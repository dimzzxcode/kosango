<?php

namespace App\Http\Controllers;

use App\Events\InvoicePaid;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * Handle a payment gateway callback.
     *
     * The manual transfer driver has no asynchronous callback, so the route
     * stays dormant until a real gateway is configured.
     */
    public function payment(Request $request, PaymentGatewayManager $gateways): JsonResponse
    {
        if (config('booking.payment_driver') === 'manual') {
            abort(404);
        }

        $signature = (string) $request->header('X-Payment-Signature', '');
        $secret = (string) config('booking.payment_webhook_secret');

        if ($secret === '' || ! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)) {
            abort(401, 'Signature webhook tidak valid.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        $payment = $gateways->driver()->handleWebhook($payload);

        if ($payment === null) {
            return response()->json(['received' => true]);
        }

        $invoice = $payment->invoice()->with('booking')->firstOrFail();

        InvoicePaid::dispatch($invoice);

        return response()->json(['received' => true]);
    }
}
