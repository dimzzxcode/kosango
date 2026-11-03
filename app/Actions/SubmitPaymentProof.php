<?php

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\ImageStorageService;
use App\Services\Notifier;
use App\Services\StatusHistoryRecorder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitPaymentProof
{
    public function __construct(
        private readonly ImageStorageService $images,
        private readonly StatusHistoryRecorder $histories,
        private readonly Notifier $notifier,
    ) {}

    public function handle(
        User $user,
        Booking $booking,
        Invoice $invoice,
        int $amount,
        UploadedFile $proof,
        ?int $bankAccountId = null,
    ): Payment {
        return DB::transaction(function () use ($user, $booking, $invoice, $amount, $proof, $bankAccountId): Payment {
            $invoice = Invoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($invoice->booking_id !== $booking->id) {
                throw ValidationException::withMessages(['invoice' => 'Tagihan tidak sesuai dengan booking.']);
            }

            if ($booking->user_id !== $user->id) {
                throw ValidationException::withMessages(['booking' => 'Booking bukan milik Anda.']);
            }

            if (! $booking->isActive()) {
                throw ValidationException::withMessages(['booking' => 'Booking ini sudah tidak aktif.']);
            }

            if (! $invoice->status->isOpen()) {
                throw ValidationException::withMessages(['invoice' => 'Tagihan ini sudah tidak dapat dibayar.']);
            }

            if ($invoice->hasWaitingVerification()) {
                throw ValidationException::withMessages([
                    'amount' => 'Masih ada bukti pembayaran yang menunggu verifikasi untuk tagihan ini.',
                ]);
            }

            if ($invoice->uploadIsLocked()) {
                throw ValidationException::withMessages([
                    'proof' => 'Upload bukti dikunci karena sudah 3 kali ditolak. Silakan hubungi admin.',
                ]);
            }

            $remaining = $invoice->remaining();

            if ($amount <= 0 || $amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran harus lebih dari 0 dan tidak melebihi sisa tagihan.',
                ]);
            }

            if ($invoice->allow_partial) {
                if ($invoice->paid_amount === 0) {
                    $minimum = (int) ceil($invoice->amount * config('booking.min_down_payment_percent') / 100);
                    $minimum = min($minimum, $remaining);

                    if ($amount < $minimum) {
                        throw ValidationException::withMessages([
                            'amount' => 'Pembayaran pertama minimal '.number_format($minimum, 0, ',', '.').' rupiah.',
                        ]);
                    }
                }
            } elseif ($amount !== $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Tagihan ini harus dibayar penuh sebesar sisa tagihan.',
                ]);
            }

            $path = $this->images->storePrivate($proof, 'payment-proofs');

            $payment = $invoice->payments()->create([
                'method' => PaymentMethod::ManualTransfer,
                'status' => PaymentStatus::WaitingVerification,
                'amount' => $amount,
                'bank_account_id' => $bankAccountId,
                'proof_path' => $path,
            ]);

            $this->histories->record(
                $payment,
                null,
                PaymentStatus::WaitingVerification->value,
                $user,
                'Bukti transfer diunggah.',
            );

            $this->notifier->toPropertyAdmins(
                $booking->property,
                'Bukti pembayaran baru',
                "Bukti transfer untuk tagihan {$invoice->number} menunggu verifikasi.",
                route('admin.bookings.show', $booking),
            );

            return $payment;
        });
    }
}
