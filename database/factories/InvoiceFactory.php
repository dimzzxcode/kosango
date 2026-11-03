<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $due = now()->addDays(3)->startOfDay();

        return [
            'booking_id' => Booking::factory(),
            'number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
            'amount' => 500_000,
            'late_fee' => 0,
            'paid_amount' => 0,
            'due_date' => $due,
            'grace_until' => $due->copy()->addDays(7),
            'allow_partial' => false,
            'status' => InvoiceStatus::Unpaid,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'paid_amount' => $attributes['amount'] ?? 500_000,
            'status' => InvoiceStatus::Paid,
        ]);
    }
}
