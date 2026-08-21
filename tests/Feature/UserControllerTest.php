<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
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

    public function test_index_returns_paginated_users(): void
    {
        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'roles']], 'meta']);
    }

    public function test_store_creates_user_with_role(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'role' => RoleSeeder::SUPER_ADMIN_ROLE,
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['email' => 'jane@example.com'])
            ->assertJsonFragment(['roles' => [RoleSeeder::SUPER_ADMIN_ROLE]]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_store_validates_input(): void
    {
        $this->postJson('/api/users', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        $this->postJson('/api/users', [
            'name' => 'Jane Doe',
            'email' => $this->admin->email,
            'password' => 'short',
            'role' => 'Ghost Role',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'role']);
    }

    public function test_show_returns_user_with_roles(): void
    {
        $this->getJson('/api/users/'.$this->admin->id)
            ->assertOk()
            ->assertJsonFragment(['email' => $this->admin->email])
            ->assertJsonFragment(['roles' => [RoleSeeder::SUPER_ADMIN_ROLE]]);
    }

    public function test_update_changes_user_and_role(): void
    {
        $user = User::factory()->create();

        $this->putJson('/api/users/'.$user->id, [
            'name' => 'Renamed',
            'role' => RoleSeeder::SUPER_ADMIN_ROLE,
        ])->assertOk()
            ->assertJsonFragment(['name' => 'Renamed'])
            ->assertJsonFragment(['roles' => [RoleSeeder::SUPER_ADMIN_ROLE]]);

        $this->putJson('/api/users/'.$user->id, ['password' => 'newpassword123'])
            ->assertOk();

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_destroy_deletes_user(): void
    {
        $user = User::factory()->create();

        $this->deleteJson('/api/users/'.$user->id)->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_routes_require_permissions(): void
    {
        $user = User::factory()->create();

        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/users', [
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'secret123',
        ])->assertForbidden();
        $this->putJson('/api/users/'.$user->id, ['name' => 'X'])->assertForbidden();
        $this->deleteJson('/api/users/'.$user->id)->assertForbidden();
    }
}
