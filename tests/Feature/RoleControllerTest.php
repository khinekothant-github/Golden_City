<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleControllerTest extends TestCase
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

    public function test_index_returns_roles_with_permissions(): void
    {
        $this->getJson('/api/roles')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'permissions']]])
            ->assertJsonFragment(['name' => RoleSeeder::SUPER_ADMIN_ROLE]);
    }

    public function test_store_creates_role_with_permissions(): void
    {
        $permissions = ['read_users', 'create_users'];

        $this->postJson('/api/roles', [
            'name' => 'Editor',
            'permissions' => $permissions,
        ])->assertCreated()
            ->assertJsonFragment(['name' => 'Editor'])
            ->assertJsonFragment(['permissions' => $permissions]);

        $this->assertSame($permissions, Role::findByName('Editor')->permissions->pluck('name')->all());
    }

    public function test_store_rejects_unknown_permission_names(): void
    {
        $this->postJson('/api/roles', [
            'name' => 'Editor',
            'permissions' => ['read_users', 'ghost_permission'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions.1']);
    }

    public function test_store_validates_input(): void
    {
        $this->postJson('/api/roles', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'permissions']);

        $this->postJson('/api/roles', [
            'name' => RoleSeeder::SUPER_ADMIN_ROLE,
            'permissions' => ['read_users'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_syncs_permissions(): void
    {
        $role = Role::create(['name' => 'Editor']);
        $role->syncPermissions(['read_users']);

        $this->putJson('/api/roles/'.$role->id, [
            'name' => 'Senior Editor',
            'permissions' => ['read_users', 'update_users'],
        ])->assertOk()
            ->assertJsonFragment(['name' => 'Senior Editor'])
            ->assertJsonFragment(['permissions' => ['read_users', 'update_users']]);

        $this->assertSame(
            ['read_users', 'update_users'],
            $role->fresh()->permissions->pluck('name')->all(),
        );
    }

    public function test_destroy_deletes_role(): void
    {
        $role = Role::create(['name' => 'Temp Role']);

        $this->deleteJson('/api/roles/'.$role->id)->assertOk();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_destroy_refuses_super_admin_role(): void
    {
        $this->deleteJson('/api/roles/'.$this->admin->roles()->first()->id)
            ->assertStatus(422);
    }

    public function test_destroy_refuses_role_assigned_to_users(): void
    {
        $role = Role::create(['name' => 'Temp Role']);
        $this->admin->assignRole($role);

        $this->deleteJson('/api/roles/'.$role->id)->assertStatus(422);

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_routes_require_permissions(): void
    {
        $user = User::factory()->create();

        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/roles')->assertForbidden();
        $this->postJson('/api/roles', [
            'name' => 'X',
            'permissions' => ['read_users'],
        ])->assertForbidden();
        $this->putJson('/api/roles/1', [
            'name' => 'X',
            'permissions' => ['read_users'],
        ])->assertForbidden();
        $this->deleteJson('/api/roles/1')->assertForbidden();
    }
}
