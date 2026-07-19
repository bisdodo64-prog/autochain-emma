<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Vehicle management
            'view vehicles',
            'create vehicles',
            'edit vehicles',
            'delete vehicles',
            'assign drivers',
            
            // Maintenance
            'view maintenance',
            'create maintenance',
            'edit maintenance',
            
            // Documents
            'view documents',
            'upload documents',
            'delete documents',
            'verify documents',
            
            // Drivers
            'view drivers',
            'create drivers',
            'edit drivers',
            'assign wallet',
            
            // Alerts
            'view alerts',
            'dismiss alerts',
            'create alerts',
            
            // Blockchain
            'authorize garage',
            'sync blockchain',
            'view blockchain data',
            
            // Admin
            'manage users',
            'manage roles',
            'view audit logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - All permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Fleet Manager (Gestionnaire de Parc)
        $fleetManager = Role::firstOrCreate(['name' => 'fleet_manager']);
        $fleetManager->givePermissionTo([
            'view vehicles',
            'create vehicles',
            'edit vehicles',
            'assign drivers',
            'view maintenance',
            'view documents',
            'upload documents',
            'delete documents',
            'view drivers',
            'create drivers',
            'edit drivers',
            'view alerts',
            'dismiss alerts',
            'create alerts',
            'view blockchain data',
        ]);

        // Driver (Chauffeur)
        $driver = Role::firstOrCreate(['name' => 'driver']);
        $driver->givePermissionTo([
            'view vehicles',
            'view maintenance',
            'view documents',
            'view alerts',
            'dismiss alerts',
            'view blockchain data',
        ]);

        // Authorized Garage (Garagiste Agrée)
        $garage = Role::firstOrCreate(['name' => 'garage']);
        $garage->givePermissionTo([
            'view vehicles',
            'view maintenance',
            'create maintenance',
            'edit maintenance',
            'view documents',
            'upload documents',
            'view alerts',
            'view blockchain data',
        ]);

        // Auditor / Buyer (Auditeur / Acheteur)
        $auditor = Role::firstOrCreate(['name' => 'auditor']);
        $auditor->givePermissionTo([
            'view vehicles',
            'view maintenance',
            'view documents',
            'verify documents',
            'view alerts',
            'view blockchain data',
        ]);
    }
}
