<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'name' => fake()->unique()->randomElement([
                'Standard Room',
                'Deluxe Room',
                'Executive Room',
                'Family Room',
                'Studio Room',
                'Suite Room',
            ]),
            'description' => fake()->sentence(14),
            'capacity' => fake()->numberBetween(1, 4),
        ];
    }
}
