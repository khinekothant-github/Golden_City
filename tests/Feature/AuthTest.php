<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class, UserSeeder::class]);
    }

    public function test_login_returns_token_and_permissions(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'superadmin@goldencity.test',
            'password' => 'SuperAdmin123!',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles', 'permissions']])
            ->assertJsonFragment(['roles' => [RoleSeeder::SUPER_ADMIN_ROLE]])
            ->assertJsonFragment(['permissions' => PermissionSeeder::PERMISSIONS]);
    }

    public function test_login_rejects_invalid_credentials_with_generic_message(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'superadmin@goldencity.test',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_login_does_not_reveal_unknown_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_login_validates_required_fields(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'superadmin@goldencity.test',
                'password' => 'wrong-password',
            ]);
        }

        $this->postJson('/api/login', [
            'email' => 'superadmin@goldencity.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::first();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_user_endpoint_returns_roles_and_permissions(): void
    {
        $user = User::first();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)->getJson('/api/user')
            ->assertOk()
            ->assertJsonFragment(['roles' => [RoleSeeder::SUPER_ADMIN_ROLE]])
            ->assertJsonFragment(['permissions' => PermissionSeeder::PERMISSIONS]);
    }
}
