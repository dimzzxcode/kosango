<?php

namespace Database\Factories;

use App\Enums\RefundInitiator;
use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'initiated_by' => RefundInitiator::Customer,
            'amount' => 250_000,
            'status' => RefundStatus::Requested,
            'bank_name' => 'BCA',
            'account_number' => fake()->numerify('##########'),
            'account_holder' => fake()->name(),
            'processed_at' => null,
        ];
    }

    public function awaitingAccount(): static
    {
        return $this->state(fn (): array => [
            'initiated_by' => RefundInitiator::Admin,
            'status' => RefundStatus::AwaitingAccount,
            'bank_name' => null,
            'account_number' => null,
            'account_holder' => null,
        ]);
    }
}
