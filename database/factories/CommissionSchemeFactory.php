<?php

namespace Database\Factories;

use App\Models\CommissionScheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionScheme>
 */
class CommissionSchemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Standard '.fake()->numberBetween(1, 10).'%',
            'rate' => fake()->randomFloat(2, 1, 5),
        ];
    }

    /**
     * Indicate that the scheme has no rate yet (negotiated per deal).
     */
    public function withoutRate(): static
    {
        return $this->state(fn () => [
            'rate' => null,
        ]);
    }
}
