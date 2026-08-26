<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Enums\PurchasePurpose;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'contact_number' => fake()->unique()->e164PhoneNumber(),
            'type' => CustomerType::New->value,
            'appointment_time' => fake()->dateTimeBetween('-1 week', '+1 week'),
            'purchase_purpose' => fake()->randomElement(PurchasePurpose::cases())->value,
        ];
    }

    /**
     * Indicate that the customer is a returning customer.
     */
    public function oldCustomer(): static
    {
        return $this->state(fn () => [
            'type' => CustomerType::Old->value,
            'number_of_visits' => fake()->numberBetween(1, 5),
        ]);
    }

    /**
     * Indicate that no appointment has been booked yet.
     */
    public function withoutAppointment(): static
    {
        return $this->state(fn () => [
            'appointment_time' => null,
        ]);
    }
}
