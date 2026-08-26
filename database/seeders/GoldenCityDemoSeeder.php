<?php

namespace Database\Seeders;

use App\Enums\Sentiment;
use App\Enums\UnitStatus;
use App\Models\Agency;
use App\Models\Building;
use App\Models\CommissionScheme;
use App\Models\Customer;
use App\Models\CustomerUnitInterest;
use App\Models\Event;
use App\Models\Phase;
use App\Models\Sale;
use App\Models\Source;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GoldenCityDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed reference data, a demo phase/building/unit tree, and one customer
     * walked through the full lifecycle (appointment → visit feedback → source → booking).
     */
    public function run(): void
    {
        // Reference data
        $schemes = CommissionScheme::factory()->count(2)->create();
        $agencies = Agency::factory()->count(2)->withScheme()->create();
        $expo = Event::factory()->past()->create(['name' => 'Golden City Property Expo 2026']);

        // Inventory tree
        $phase = Phase::factory()->create(['name' => 'Phase 1', 'location' => 'Golden City']);
        $building = Building::factory()->for($phase)->create(['name' => 'Tower A']);
        $units = Unit::factory()->count(12)->for($building)->create();

        // Landed plots hang directly off the phase
        $plots = Unit::factory()->count(3)->inPhase($phase)->create();

        // Full customer journey
        $salesPerson = User::query()->first() ?? User::factory()->create();
        $customer = Customer::factory()->create([
            'name' => 'U Aung Kyaw',
            'appointment_time' => now()->addDays(2),
        ]);

        $customer->salePersons()->attach($salesPerson->id);

        [$liked, $disliked] = $units->random(2);

        CustomerUnitInterest::query()->create([
            'customer_id' => $customer->id,
            'unit_id' => $plots[0]->id,
            'sentiment' => Sentiment::Preferred,
        ]);

        $customer->recordVisit();

        CustomerUnitInterest::query()->create([
            'customer_id' => $customer->id,
            'unit_id' => $liked->id,
            'sentiment' => Sentiment::Liked,
            'comment' => 'Bright corner layout, quiet floor.',
        ]);

        CustomerUnitInterest::query()->create([
            'customer_id' => $customer->id,
            'unit_id' => $disliked->id,
            'sentiment' => Sentiment::Disliked,
            'comment' => 'Faces the main road, too noisy.',
        ]);

        Source::query()->create([
            'customer_id' => $customer->id,
            'category' => 'event',
            'sub_category' => 'property expo',
            'agency_id' => $agencies[0]->id,
            'event_id' => $expo->id,
        ]);

        $booked = $units->first();
        $booked->update(['status' => UnitStatus::Reserved->value]);

        Sale::factory()
            ->for($booked)
            ->for($customer)
            ->for($salesPerson, 'salePerson')
            ->for($agencies[0], 'agency')
            ->for($schemes[0], 'commissionScheme')
            ->create(['promotion_remark' => 'Expo discount 5%']);
    }
}
