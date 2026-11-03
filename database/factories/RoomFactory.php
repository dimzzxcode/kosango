<?php

namespace Database\Factories;

use App\Enums\RoomStatus;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
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
            'room_type_id' => RoomType::factory(),
            'name' => 'Kamar '.fake()->unique()->numerify('###'),
            'status' => RoomStatus::Available,
        ];
    }

    public function maintenance(): static
    {
        return $this->state(fn (): array => ['status' => RoomStatus::Maintenance]);
    }
}
