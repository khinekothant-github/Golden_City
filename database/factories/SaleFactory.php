<?php

namespace Database\Factories;

use App\Enums\ProcessType;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'customer_id' => Customer::factory(),
            'sale_person_id' => User::factory(),
            'price' => fake()->randomFloat(2, 80_000, 800_000),
            'payment_plan' => null,
            'process_type' => ProcessType::Booking->value,
            'sold_at' => now(),
        ];
    }

    /**
     * Indicate the sale row records a final sale.
     */
    public function finalSale(): static
    {
        return $this->state(fn () => [
            'process_type' => ProcessType::Sale->value,
        ]);
    }

    /**
     * Indicate the sale row records a reservation.
     */
    public function reservation(): static
    {
        return $this->state(fn () => [
            'process_type' => ProcessType::Reservation->value,
        ]);
    }
}
