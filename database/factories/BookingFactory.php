<?php

namespace Database\Factories;

use App\Enums\BookingPeriod;
use App\Enums\BookingStatus;
use App\Enums\PaymentPlan;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'BK-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
            'property_id' => Property::factory(),
            'room_type_id' => RoomType::factory(),
            'room_id' => Room::factory(),
            'user_id' => User::factory(),
            'period' => BookingPeriod::Daily,
            'duration' => 2,
            'payment_plan' => PaymentPlan::Full,
            'unit_price' => 500_000,
            'total_price' => 1_000_000,
            'guests' => 2,
            'check_in' => now()->addDays(7)->startOfDay(),
            'check_out' => now()->addDays(9)->startOfDay(),
            'status' => BookingStatus::PendingPayment,
            'expires_at' => now()->addHours(24),
            'terms_accepted_at' => now(),
            'terms_version' => 1,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status' => BookingStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => BookingStatus::Completed,
            'confirmed_at' => now()->subDays(10),
            'completed_at' => now(),
        ]);
    }
}
