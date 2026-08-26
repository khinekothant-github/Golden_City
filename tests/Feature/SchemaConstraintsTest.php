<?php

namespace Tests\Feature;

use App\Enums\Sentiment;
use App\Models\Building;
use App\Models\Customer;
use App\Models\Phase;
use App\Models\Source;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchemaConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_requires_a_parent(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('units_parent_check is enforced by PostgreSQL only.');
        }

        $this->expectException(QueryException::class);

        Unit::factory()->create(['building_id' => null, 'phase_id' => null]);
    }

    public function test_unit_rejects_having_both_parents(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('units_parent_check is enforced by PostgreSQL only.');
        }

        $phase = Phase::factory()->create();

        $this->expectException(QueryException::class);

        Unit::factory()->create([
            'building_id' => Building::factory(),
            'phase_id' => $phase->id,
        ]);
    }

    public function test_unit_names_are_unique_within_a_building(): void
    {
        $building = Building::factory()->create();
        Unit::factory()->for($building)->create(['name' => 'U-001']);

        $this->expectException(QueryException::class);

        Unit::factory()->for($building)->create(['name' => 'U-001']);
    }

    public function test_unit_names_may_repeat_across_buildings(): void
    {
        $first = Unit::factory()->for(Building::factory())->create(['name' => 'U-001']);
        $second = Unit::factory()->for(Building::factory())->create(['name' => 'U-001']);

        $this->assertModelExists($first);
        $this->assertModelExists($second);
    }

    public function test_phase_direct_unit_names_are_unique_within_the_phase(): void
    {
        $phase = Phase::factory()->create();
        Unit::factory()->inPhase($phase)->create(['name' => 'Plot-01']);

        $this->expectException(QueryException::class);

        Unit::factory()->inPhase($phase)->create(['name' => 'Plot-01']);
    }

    public function test_contact_numbers_are_unique(): void
    {
        Customer::factory()->create(['contact_number' => '+959700000001']);

        $this->expectException(QueryException::class);

        Customer::factory()->create(['contact_number' => '+959700000001']);
    }

    public function test_customer_sale_person_pairs_are_unique(): void
    {
        $customer = Customer::factory()->create();
        $salesPerson = User::factory()->create();
        $customer->salePersons()->attach($salesPerson);

        $this->expectException(QueryException::class);

        $customer->salePersons()->attach($salesPerson);
    }

    public function test_interests_allow_multiple_sentiments_per_pair_but_not_duplicates(): void
    {
        $customer = Customer::factory()->create();
        $unit = Unit::factory()->create();

        $customer->interestedUnits()->attach($unit, ['sentiment' => Sentiment::Preferred->value]);
        $customer->interestedUnits()->attach($unit, ['sentiment' => Sentiment::Liked->value]);

        $this->assertSame(2, $customer->interestedUnits()->count());

        $this->expectException(QueryException::class);

        $customer->interestedUnits()->attach($unit, ['sentiment' => Sentiment::Preferred->value]);
    }

    public function test_a_customer_has_at_most_one_source(): void
    {
        $customer = Customer::factory()->withoutAppointment()->create();
        Source::query()->create(['customer_id' => $customer->id, 'category' => 'walk-in']);

        $this->expectException(QueryException::class);

        Source::query()->create(['customer_id' => $customer->id, 'category' => 'referral']);
    }
}
