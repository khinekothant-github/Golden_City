<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class, UserSeeder::class]);
    }

    public function test_index_returns_all_permissions(): void
    {
        $this->actingAs(User::first(), 'sanctum')
            ->getJson('/api/permissions')
            ->assertOk()
            ->assertJsonCount(count(PermissionSeeder::PERMISSIONS))
            ->assertJsonFragment(['name' => 'read_users']);
    }

    public function test_index_requires_read_roles_permission(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/permissions')
            ->assertForbidden();
    }
}
