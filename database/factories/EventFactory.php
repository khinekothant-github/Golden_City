<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'name' => 'Golden City '.fake()->word().' Expo',
            'starts_at' => $startsAt,
            'ends_at' => fake()->dateTimeInInterval($startsAt, '+3 days'),
        ];
    }

    /**
     * Indicate that the event has ended already.
     */
    public function past(): static
    {
        return $this->state(fn () => [
            'starts_at' => fake()->dateTimeBetween('-6 months', '-1 month'),
            'ends_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }
}
