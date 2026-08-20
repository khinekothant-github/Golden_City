<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate([
            'email' => config('app.super_admin_email', 'superadmin@goldencity.test'),
        ], [
            'name' => 'Super Admin',
            'password' => config('app.super_admin_password', 'SuperAdmin123!'),
        ]);

        $user->assignRole(RoleSeeder::SUPER_ADMIN_ROLE);
    }
}
