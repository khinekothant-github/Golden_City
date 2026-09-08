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
        'read_phases',
        'create_phases',
        'update_phases',
        'delete_phases',
        'read_buildings',
        'create_buildings',
        'update_buildings',
        'delete_buildings',
        'read_units',
        'create_units',
        'update_units',
        'delete_units',
        'read_agencies',
        'create_agencies',
        'update_agencies',
        'delete_agencies',
        'read_commission_schemes',
        'create_commission_schemes',
        'update_commission_schemes',
        'delete_commission_schemes',
        'read_events',
        'create_events',
        'update_events',
        'delete_events',
        'read_customers',
        'create_customers',
        'update_customers',
        'delete_customers',
        'record_customer_visits',
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
