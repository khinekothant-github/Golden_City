<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Enums\PhaseType;
use App\Enums\ProcessType;
use App\Enums\UnitStatus;
use App\Models\Agency;
use App\Models\Building;
use App\Models\CommissionScheme;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Phase;
use App\Models\Sale;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactorySmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_factories_create_models_with_defaults(): void
    {
        $phase = Phase::factory()->create();
        $building = Building::factory()->for($phase)->create();
        $unit = Unit::factory()->for($building)->create();
        $scheme = CommissionScheme::factory()->withoutRate()->create();
        $agency = Agency::factory()->create(['commission_id' => $scheme->id]);
        $event = Event::factory()->past()->create();
        $customer = Customer::factory()->oldCustomer()->create();
        $sale = Sale::factory()->for($unit)->for($customer)->for($agency, 'agency')->create();

        $this->assertModelExists($phase->fresh());
        $this->assertModelExists($building->fresh());
        $this->assertModelExists($unit->fresh());
        $this->assertModelExists($scheme->fresh());
        $this->assertModelExists($agency->fresh());
        $this->assertModelExists($event->fresh());
        $this->assertModelExists($customer->fresh());
        $this->assertModelExists($sale->fresh());

        $this->assertSame(PhaseType::Residential, $phase->type);
        $this->assertSame(UnitStatus::Available, $unit->status);
        $this->assertSame(CustomerType::Old, $customer->type);
        $this->assertSame(ProcessType::Booking, $sale->process_type);
        $this->assertTrue($agency->commissionScheme->is($scheme));
        $this->assertSame(1, Building::query()->whereKey($building->id)->withCount('units')->first()->units_count);
    }

    public function test_unit_factory_states_apply(): void
    {
        $reserved = Unit::factory()->reserved()->create();
        $sold = Unit::factory()->sold()->create();
        $plot = Unit::factory()->inPhase()->create();

        $this->assertSame(UnitStatus::Reserved, $reserved->status);
        $this->assertSame(UnitStatus::Sold, $sold->status);
        $this->assertNull($plot->building_id);
        $this->assertNotNull($plot->phase_id);
        $this->assertSame(1, Unit::available()->count());
    }
}
