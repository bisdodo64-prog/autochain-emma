<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage_users',
            'manage_vehicles',
            'manage_maintenance',
            'view_reports',
            'sign_transactions',
            'authorize_garages',
            'archive_records'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $manager = Role::create(['name' => 'fleet_manager']);
        $manager->givePermissionTo(['manage_vehicles', 'view_reports', 'sign_transactions']);

        $driver = Role::create(['name' => 'driver']);
        $driver->givePermissionTo(['view_reports']);

        $garage = Role::create(['name' => 'garage']);
        $garage->givePermissionTo(['manage_maintenance', 'sign_transactions']);

        $auditor = Role::create(['name' => 'auditor']);
        $auditor->givePermissionTo(['view_reports']);
    }
}