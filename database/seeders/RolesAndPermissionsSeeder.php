<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);
        $roleCashier = Role::firstOrCreate(['name' => 'Cashier']);
        $roleStorekeeper = Role::firstOrCreate(['name' => 'Storekeeper']);
        $roleHR = Role::firstOrCreate(['name' => 'HR']);
        $roleLoader = Role::firstOrCreate(['name' => 'Loader']);
        $roleDriver = Role::firstOrCreate(['name' => 'Driver']);
        $roleSupervisor = Role::firstOrCreate(['name' => 'Supervisor']);

        // Define permissions (we can add more granular ones later, for now we manage by sections)
        $permissions = [
            'manage_users',
            'manage_settings',
            
            'view_sales',
            'manage_sales',
            
            'view_purchases',
            'manage_purchases',
            
            'view_inventory',
            'manage_inventory',
            
            'view_production',
            'manage_production',
            
            'view_hr',
            'manage_hr',
            
            'view_finance',
            'manage_finance',
            
            'view_reports',

            'view_loading',
            'manage_loading'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to Roles
        $roleAdmin->syncPermissions(Permission::all());

        $roleCashier->syncPermissions([
            'view_sales', 'manage_sales',
            'view_finance', 'manage_finance'
        ]);

        $roleStorekeeper->syncPermissions([
            'view_inventory', 'manage_inventory',
            'view_production', 'manage_production',
            'view_purchases', 'manage_purchases'
        ]);

        $roleHR->syncPermissions([
            'view_hr', 'manage_hr'
        ]);

        $roleLoader->syncPermissions([
            'view_loading', 'manage_loading'
        ]);

        $roleDriver->syncPermissions([
            'view_loading'
        ]);

        $roleSupervisor->syncPermissions([
            'view_hr', 'manage_hr'
        ]);

        // Assign Admin role to the first user
        $user = User::first();
        if ($user) {
            $user->assignRole('Admin');
        } else {
            // Create a default admin if none exists
            $user = User::create([
                'name' => 'المدير العام',
                'email' => 'admin@admin.com',
                'password' => bcrypt('password'),
            ]);
            $user->assignRole('Admin');
        }
    }
}
