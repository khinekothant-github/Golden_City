<?php

namespace Tests\Feature;

use App\Enums\ProcessType;
use App\Enums\Sentiment;
use App\Models\Agency;
use App\Models\Customer;
use App\Models\CustomerUnitInterest;
use App\Models\Event;
use App\Models\Sale;
use App\Models\Source;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_appointment_to_booking_journey(): void
    {
        $salesPerson = User::factory()->create();
        $units = Unit::factory()->count(3)->create();
        $agency = Agency::factory()->withScheme()->create();
        $event = Event::factory()->past()->create();

        // Appointment booked → customer row created with identity + time
        $customer = Customer::factory()->create([
            'appointment_time' => now()->addDays(2),
        ]);
        $customer->salePersons()->attach($salesPerson);

        // Preferred units before the visit
        $customer->interestedUnits()->attach($units[0], ['sentiment' => Sentiment::Preferred->value]);
        $customer->interestedUnits()->attach($units[1], ['sentiment' => Sentiment::Preferred->value]);

        // After the visit: counter, feedback with comments, attribution
        $customer->recordVisit();

        $customer->interestedUnits()->attach($units[0], [
            'sentiment' => Sentiment::Liked->value,
            'comment' => 'Bright corner layout.',
        ]);
        $customer->interestedUnits()->attach($units[2], [
            'sentiment' => Sentiment::Disliked->value,
            'comment' => 'Faces the main road.',
        ]);

        Source::query()->create([
            'customer_id' => $customer->id,
            'category' => 'event',
            'sub_category' => 'property expo',
            'agency_id' => $agency->id,
            'event_id' => $event->id,
        ]);

        // Booking
        Sale::factory()
            ->for($units[0])
            ->for($customer)
            ->for($salesPerson, 'salePerson')
            ->create();

        $customer = $customer->fresh();

        $this->assertSame(1, $customer->number_of_visits);
        $this->assertTrue($customer->salePersons->contains($salesPerson));
        $this->assertCount(4, $customer->interestedUnits);

        $liked = $customer->interestedUnits->first(
            fn (Unit $unit): bool => $unit->id === $units[0]->id && $unit->pivot->sentiment === Sentiment::Liked
        );
        $this->assertInstanceOf(CustomerUnitInterest::class, $liked->pivot);
        $this->assertSame('Bright corner layout.', $liked->pivot->comment);

        $source = $customer->source;
        $this->assertSame('event', $source->category);
        $this->assertTrue($source->agency->is($agency));
        $this->assertTrue($source->event->is($event));

        $sale = $customer->sales->sole();
        $this->assertSame(ProcessType::Booking, $sale->process_type);
        $this->assertTrue($sale->unit->is($units[0]));
        $this->assertTrue($sale->salePerson->is($salesPerson));
    }

    public function test_record_visit_increments_the_counter(): void
    {
        $customer = Customer::factory()->withoutAppointment()->create();

        $customer->recordVisit();
        $customer->recordVisit();

        $this->assertSame(2, $customer->fresh()->number_of_visits);
    }

    public function test_latest_sale_returns_the_most_recent_row(): void
    {
        $unit = Unit::factory()->create();

        $older = Sale::factory()->for($unit)->create(['sold_at' => now()->subMonths(6)]);
        $newer = Sale::factory()->finalSale()->for($unit)->create(['sold_at' => now()]);

        $this->assertTrue($unit->latestSale->is($newer));
        $this->assertNotSame($older->id, $unit->latestSale->id);
    }

    public function test_customer_can_have_many_sale_persons(): void
    {
        $customer = Customer::factory()->create();

        $customer->salePersons()->attach(User::factory()->count(3)->create());

        $this->assertCount(3, $customer->salePersons);
    }
}
