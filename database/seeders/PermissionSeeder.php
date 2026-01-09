<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Dashboard
            'view_dashboard',
            'view_analytics',

            // Users
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',

            // Test Management
            'view_tests',
            'create_tests',
            'edit_tests',
            'delete_tests',
            'view_test_categories',
            'manage_test_categories',

            // Subscription Management
            'view_subscriptions',
            'manage_subscription_plans',
            'manage_subscription_prices',
            'purchase_subscription',
            'view_invoices',
            'manage_payments',

            // Company Management
            'view_companies',
            'create_companies',
            'edit_companies',
            'delete_companies',
            'manage_company_admins',

            // Participant Management
            'view_participants',
            'create_participants',
            'edit_participants',
            'delete_participants',
            'import_participants',
            'assign_tests',
            'ban_participants',

            // Test Results
            'view_test_results',
            'export_test_results',

            // Monitoring
            'view_monitoring',
            'view_test_sessions',
            'view_cheat_detections',

            // Cart & Transactions
            'view_cart',
            'manage_cart',
            'view_transactions',
            'purchase_tests',

            // Settings
            'manage_settings',
            'manage_menus',

            // Public User
            'take_tests',
            'view_own_results',
            'view_own_profile',
            'manage_own_profile',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $tenantAdmin = Role::create(['name' => 'tenant_admin']);
        $tenantAdmin->givePermissionTo([
            'view_dashboard',
            'view_analytics',
            'view_tests',
            'purchase_subscription',
            'view_invoices',
            'view_participants',
            'create_participants',
            'edit_participants',
            'delete_participants',
            'import_participants',
            'assign_tests',
            'ban_participants',
            'view_test_results',
            'export_test_results',
            'view_monitoring',
            'view_test_sessions',
            'view_cheat_detections',
            'manage_company_admins',
        ]);

        $publicUser = Role::create(['name' => 'public_user']);
        $publicUser->givePermissionTo([
            'view_dashboard',
            'view_tests',
            'view_cart',
            'manage_cart',
            'view_transactions',
            'purchase_tests',
            'take_tests',
            'view_own_results',
            'view_own_profile',
            'manage_own_profile',
        ]);
    }
}
