<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'method' => PaymentMethod::ManualTransfer,
            'status' => PaymentStatus::WaitingVerification,
            'amount' => 500_000,
            'proof_path' => null,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'verified_at' => now(),
            'proof_path' => 'payment-proofs/demo.jpg',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Rejected,
            'rejection_reason' => 'Bukti transfer tidak jelas.',
        ]);
    }
}
