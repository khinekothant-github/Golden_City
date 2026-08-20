<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /** @var list<string> */
    public const PERMISSIONS = [
        'read_users',
        'create_users',
        'update_users',
        'delete_users',
        'read_roles',
        'create_roles',
        'update_roles',
        'delete_roles',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
