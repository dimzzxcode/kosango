<?php

namespace Database\Factories;

use App\Enums\BookingPeriod;
use App\Models\RoomType;
use App\Models\RoomTypePrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomTypePrice>
 */
class RoomTypePriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'period' => BookingPeriod::Monthly,
            'price' => fake()->numberBetween(6, 40) * 500_000,
            'min_duration' => 1,
            'max_duration' => 24,
            'is_active' => true,
        ];
    }

    public function daily(): static
    {
        return $this->state(fn (): array => [
            'period' => BookingPeriod::Daily,
            'price' => fake()->numberBetween(2, 20) * 50_000,
            'max_duration' => 30,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (): array => [
            'period' => BookingPeriod::Yearly,
            'price' => fake()->numberBetween(50, 300) * 500_000,
            'max_duration' => 5,
        ]);
    }
}
