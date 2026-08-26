<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Phase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phase_id' => Phase::factory(),
            'name' => 'Tower '.fake()->unique()->numberBetween(1, 50),
            'building_type' => null,
        ];
    }
}
