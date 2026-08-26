<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\CommissionScheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agency>
 */
class AgencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'staff' => fake()->name(),
            'commission_id' => null,
            'phone' => fake()->e164PhoneNumber(),
            'sale_staff_phone' => fake()->e164PhoneNumber(),
        ];
    }

    /**
     * Indicate that the agency has a default commission scheme.
     */
    public function withScheme(): static
    {
        return $this->state(fn () => [
            'commission_id' => CommissionScheme::factory(),
        ]);
    }
}
