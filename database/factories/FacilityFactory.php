<?php

namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'WiFi cepat', 'AC', 'Parkir', 'Dapur bersama', 'Laundry',
                'Kolam renang', 'Sarapan', 'Resepsionis 24 jam', 'Gym', 'Spa',
            ]).' '.fake()->unique()->numerify('##'),
            'icon' => null,
        ];
    }
}
