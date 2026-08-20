<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    public const SUPER_ADMIN_ROLE = 'Super Admin';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => self::SUPER_ADMIN_ROLE]);
        $role->syncPermissions(PermissionSeeder::PERMISSIONS);
    }
}
