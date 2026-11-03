<?php

namespace Database\Factories;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\City;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'city_id' => City::factory(),
            'type' => fake()->randomElement(PropertyType::cases()),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'address' => fake()->address(),
            'description' => fake()->paragraphs(2, true),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'whatsapp_number' => fake()->numerify('08##########'),
            'status' => PropertyStatus::Published,
            'thumbnail_path' => 'properties/demo.jpg',
            'thumbnail_alt' => $name,
            'rating_avg' => fake()->randomFloat(2, 3.8, 5.0),
            'reviews_count' => fake()->numberBetween(12, 850),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => PropertyStatus::Draft]);
    }

    public function hotel(): static
    {
        return $this->state(fn (): array => ['type' => PropertyType::Hotel]);
    }

    public function kos(): static
    {
        return $this->state(fn (): array => ['type' => PropertyType::Kos]);
    }
}
