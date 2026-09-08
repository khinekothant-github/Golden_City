<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
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

    public function test_index_returns_paginated_customers(): void
    {
        Customer::factory()->count(3)->create();

        $this->getJson('/api/customers')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'contact_number', 'type']], 'meta']);
    }

    public function test_index_searches_by_contact_number(): void
    {
        $customer = Customer::factory()->create(['contact_number' => '09912345678']);
        Customer::factory()->create(['contact_number' => '09987654321']);

        $this->getJson('/api/customers?search=12345678')
            ->assertOk()
            ->assertJsonFragment(['contact_number' => '09912345678'])
            ->assertJsonMissing(['contact_number' => '09987654321']);

        $this->assertSame('09912345678', $customer->contact_number);
    }

    public function test_store_creates_customer_with_booking(): void
    {
        $salePerson = User::factory()->create();
        $units = Unit::factory()->count(2)->create();

        $response = $this->postJson('/api/customers', [
            'name' => 'Ma Eaindra Wai',
            'contact_number' => '09423121124',
            'email' => 'eaindra@gmail.com',
            'type' => 'new',
            'appointment_time' => '2026-04-28 11:30:00',
            'sale_person_ids' => [$salePerson->id],
            'preferred_unit_ids' => $units->pluck('id')->all(),
            'purchase_purpose' => 'own_stay',
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['contact_number' => '09423121124'])
            ->assertJsonPath('data.number_of_visits', 0);

        $customerId = $response->json('data.id');

        $this->assertDatabaseHas('customers', ['id' => $customerId, 'contact_number' => '09423121124']);
        $this->assertDatabaseHas('customer_sale_person', ['customer_id' => $customerId, 'sale_person_id' => $salePerson->id]);

        foreach ($units as $unit) {
            $this->assertDatabaseHas('customer_unit_interests', [
                'customer_id' => $customerId,
                'unit_id' => $unit->id,
                'sentiment' => 'preferred',
            ]);
        }
    }

    public function test_store_validates_input(): void
    {
        $this->postJson('/api/customers', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'contact_number', 'type']);

        $existing = Customer::factory()->create();

        $this->postJson('/api/customers', [
            'name' => 'Dup',
            'contact_number' => $existing->contact_number,
            'type' => 'ghost',
            'sale_person_ids' => [999999],
            'preferred_unit_ids' => [999999],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['contact_number', 'type', 'sale_person_ids.0', 'preferred_unit_ids.0']);
    }

    public function test_show_returns_customer_with_relations(): void
    {
        $customer = Customer::factory()->create();
        $customer->salePersons()->attach(User::factory()->create());

        $this->getJson('/api/customers/'.$customer->id)
            ->assertOk()
            ->assertJsonFragment(['contact_number' => $customer->contact_number])
            ->assertJsonStructure(['data' => ['sale_persons', 'interests', 'source']]);
    }

    public function test_update_reschedules_appointment(): void
    {
        $customer = Customer::factory()->create();
        $salePerson = User::factory()->create();

        $this->putJson('/api/customers/'.$customer->id, [
            'appointment_time' => '2026-05-01 10:00:00',
            'sale_person_ids' => [$salePerson->id],
        ])->assertOk()
            ->assertJsonFragment(['id' => $customer->id]);

        $this->assertDatabaseHas('customer_sale_person', [
            'customer_id' => $customer->id,
            'sale_person_id' => $salePerson->id,
        ]);
    }

    public function test_destroy_deletes_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->deleteJson('/api/customers/'.$customer->id)->assertOk();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_destroy_is_blocked_when_customer_has_sales(): void
    {
        $customer = Customer::factory()->create();
        Sale::factory()->for($customer)->create();

        $this->deleteJson('/api/customers/'.$customer->id)
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Customer has sales history and cannot be deleted.']);
    }

    public function test_store_accepts_minimal_payload_for_walk_ins(): void
    {
        $response = $this->postJson('/api/customers', [
            'name' => 'Walk In Guest',
            'contact_number' => '09000000001',
            'type' => 'new',
        ]);

        $response->assertCreated()->assertJsonPath('data.number_of_visits', 0);

        $this->assertDatabaseHas('customers', ['contact_number' => '09000000001']);
    }

    public function test_store_reuses_contact_number_after_soft_delete(): void
    {
        $customer = Customer::factory()->create(['contact_number' => '09000000002']);
        $customer->delete();

        $this->postJson('/api/customers', [
            'name' => 'Replacement',
            'contact_number' => '09000000002',
            'type' => 'new',
        ])->assertCreated();
    }

    public function test_update_rejects_duplicate_contact_number(): void
    {
        $existing = Customer::factory()->create();
        $customer = Customer::factory()->create();

        $this->putJson('/api/customers/'.$customer->id, [
            'contact_number' => $existing->contact_number,
        ])->assertUnprocessable()->assertJsonValidationErrors(['contact_number']);
    }

    public function test_show_returns_404_for_missing_customer(): void
    {
        $this->getJson('/api/customers/999999')->assertNotFound();
    }

    public function test_routes_require_authentication_and_permissions(): void
    {
        $customer = Customer::factory()->create();

        $this->app['auth']->forgetGuards();

        $this->getJson('/api/customers')->assertUnauthorized();
        $this->postJson('/api/customers', ['name' => 'X'])->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/customers')->assertForbidden();
        $this->postJson('/api/customers', ['name' => 'X'])->assertForbidden();
        $this->putJson('/api/customers/'.$customer->id, ['name' => 'X'])->assertForbidden();
        $this->deleteJson('/api/customers/'.$customer->id)->assertForbidden();
        $this->postJson('/api/customers/'.$customer->id.'/visits', [])->assertForbidden();
    }
}
