<?php

namespace Database\Factories;

use App\Enums\UnitStatus;
use App\Models\Building;
use App\Models\Phase;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected static int $unitSequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'phase_id' => null,
            'name' => str_pad((string) (++static::$unitSequence), 3, '0', STR_PAD_LEFT),
            'type' => null,
            'room_description' => null,
            'base_price' => fake()->randomFloat(2, 50_000, 500_000),
            'status' => UnitStatus::Available->value,
        ];
    }

    /**
     * Indicate that the unit hangs directly off a phase with no building.
     */
    public function inPhase(?Phase $phase = null): static
    {
        return $this->state(fn () => [
            'building_id' => null,
            'phase_id' => $phase?->id ?? Phase::factory(),
        ]);
    }

    /**
     * Indicate that the unit is reserved.
     */
    public function reserved(): static
    {
        return $this->state(fn () => [
            'status' => UnitStatus::Reserved->value,
        ]);
    }

    /**
     * Indicate that the unit is sold.
     */
    public function sold(): static
    {
        return $this->state(fn () => [
            'status' => UnitStatus::Sold->value,
        ]);
    }
}
