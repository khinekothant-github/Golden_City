<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerVisitTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class, UserSeeder::class]);

        $this->admin = User::first();
        $this->actingAs($this->admin, 'sanctum');
    }

    public function test_record_visit_increments_counter_and_stores_feedback_and_source(): void
    {
        $customer = Customer::factory()->create(['number_of_visits' => 0]);
        $liked = Unit::factory()->create();
        $disliked = Unit::factory()->create();
        $agency = Agency::factory()->create();
        $event = Event::factory()->create();

        $response = $this->postJson('/api/customers/'.$customer->id.'/visits', [
            'purchase_purpose' => 'investment',
            'feedbacks' => [
                ['unit_id' => $liked->id, 'sentiment' => 'liked', 'comment' => 'Bright corner layout.'],
                ['unit_id' => $disliked->id, 'sentiment' => 'disliked', 'comment' => 'Faces the main road.'],
            ],
            'source' => [
                'category' => 'event',
                'sub_category' => 'property expo',
                'agency_id' => $agency->id,
                'event_id' => $event->id,
            ],
        ]);

        $response->assertOk()->assertJsonPath('data.number_of_visits', 1);

        $this->assertSame(1, $customer->fresh()->number_of_visits);
        $this->assertDatabaseHas('customer_unit_interests', [
            'customer_id' => $customer->id,
            'unit_id' => $liked->id,
            'sentiment' => 'liked',
        ]);
        $this->assertDatabaseHas('customer_unit_interests', [
            'customer_id' => $customer->id,
            'unit_id' => $disliked->id,
            'sentiment' => 'disliked',
        ]);
        $this->assertDatabaseHas('sources', [
            'customer_id' => $customer->id,
            'category' => 'event',
        ]);
    }

    public function test_second_visit_increments_again_and_upserts_source(): void
    {
        $customer = Customer::factory()->create(['number_of_visits' => 0]);
        $unit = Unit::factory()->create();

        $this->postJson('/api/customers/'.$customer->id.'/visits', [
            'feedbacks' => [['unit_id' => $unit->id, 'sentiment' => 'liked']],
            'source' => ['category' => 'walk_in'],
        ])->assertOk()->assertJsonPath('data.number_of_visits', 1);

        $otherUnit = Unit::factory()->create();
        $this->postJson('/api/customers/'.$customer->id.'/visits', [
            'feedbacks' => [['unit_id' => $otherUnit->id, 'sentiment' => 'disliked']],
            'source' => ['category' => 'agency'],
        ])->assertOk()->assertJsonPath('data.number_of_visits', 2);

        $this->assertDatabaseCount('sources', 1);
        $this->assertSame('agency', $customer->fresh()->source->category);
    }

    public function test_record_visit_rejects_duplicate_feedback(): void
    {
        $customer = Customer::factory()->create();
        $unit = Unit::factory()->create();

        $payload = [
            'feedbacks' => [['unit_id' => $unit->id, 'sentiment' => 'liked']],
        ];

        $this->postJson('/api/customers/'.$customer->id.'/visits', $payload)->assertOk();
        $this->postJson('/api/customers/'.$customer->id.'/visits', $payload)->assertUnprocessable();
    }

    public function test_record_visit_validates_input(): void
    {
        $customer = Customer::factory()->create();

        $this->postJson('/api/customers/'.$customer->id.'/visits', [
            'feedbacks' => [
                ['unit_id' => 999999, 'sentiment' => 'liked'],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors(['feedbacks.0.unit_id']);

        $unit = Unit::factory()->create();

        $this->postJson('/api/customers/'.$customer->id.'/visits', [
            'feedbacks' => [
                ['unit_id' => $unit->id, 'sentiment' => 'preferred'],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors(['feedbacks.0.sentiment']);

        $this->postJson('/api/customers/'.$customer->id.'/visits', [
            'source' => ['agency_id' => 999999, 'event_id' => 999999],
        ])->assertUnprocessable()->assertJsonValidationErrors(['source.category', 'source.agency_id', 'source.event_id']);
    }

    public function test_visit_with_empty_payload_only_increments(): void
    {
        $customer = Customer::factory()->create(['number_of_visits' => 0]);

        $this->postJson('/api/customers/'.$customer->id.'/visits', [])
            ->assertOk()->assertJsonPath('data.number_of_visits', 1);

        $this->assertSame(1, $customer->fresh()->number_of_visits);
        $this->assertDatabaseCount('customer_unit_interests', 0);
        $this->assertDatabaseCount('sources', 0);
    }

    public function test_same_unit_can_hold_liked_and_disliked(): void
    {
        $customer = Customer::factory()->create(['number_of_visits' => 0]);
        $unit = Unit::factory()->create();

        $this->postJson('/api/customers/'.$customer->id.'/visits', [
            'feedbacks' => [
                ['unit_id' => $unit->id, 'sentiment' => 'liked', 'comment' => 'View.'],
                ['unit_id' => $unit->id, 'sentiment' => 'disliked', 'comment' => 'Noise.'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('customer_unit_interests', [
            'customer_id' => $customer->id, 'unit_id' => $unit->id, 'sentiment' => 'liked',
        ]);
        $this->assertDatabaseHas('customer_unit_interests', [
            'customer_id' => $customer->id, 'unit_id' => $unit->id, 'sentiment' => 'disliked',
        ]);
    }

    public function test_visit_rejects_soft_deleted_agency(): void
    {
        $customer = Customer::factory()->create();
        $agency = Agency::factory()->create();
        $agency->delete();

        $this->postJson('/api/customers/'.$customer->id.'/visits', [
            'source' => ['category' => 'agency', 'agency_id' => $agency->id],
        ])->assertUnprocessable()->assertJsonValidationErrors(['source.agency_id']);
    }

    public function test_visit_returns_404_for_missing_customer(): void
    {
        $this->postJson('/api/customers/999999/visits', [])
            ->assertNotFound();
    }
}
