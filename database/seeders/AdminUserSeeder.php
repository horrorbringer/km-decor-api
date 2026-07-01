<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_products', 'create_products', 'update_products', 'delete_products',
            'view_categories', 'create_categories', 'update_categories', 'delete_categories',
            'view_brands', 'create_brands', 'update_brands', 'delete_brands',
            'view_services', 'create_services', 'update_services', 'delete_services',
            'view_portfolios', 'create_portfolios', 'update_portfolios', 'delete_portfolios',
            'view_orders', 'update_orders', 'delete_orders', 'export_orders',
            'view_inquiries', 'update_inquiries',
            'view_users', 'create_users', 'update_users', 'delete_users',
            'view_roles', 'create_roles', 'update_roles', 'delete_roles',
            'manage_settings',
            'view_media', 'upload_media', 'delete_media',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'view_products', 'create_products', 'update_products',
            'view_categories', 'create_categories', 'update_categories',
            'view_brands', 'create_brands', 'update_brands',
            'view_services', 'create_services', 'update_services',
            'view_portfolios', 'create_portfolios', 'update_portfolios',
            'view_orders', 'update_orders', 'export_orders',
            'view_inquiries', 'update_inquiries',
            'view_users', 'create_users', 'update_users',
            'view_roles', 'create_roles', 'update_roles',
            'manage_settings',
            'view_media', 'upload_media', 'delete_media',
        ]);

        $orderManager = Role::firstOrCreate(['name' => 'order_manager', 'guard_name' => 'web']);
        $orderManager->givePermissionTo([
            'view_orders', 'update_orders', 'export_orders',
            'view_products',
            'view_inquiries', 'update_inquiries',
        ]);

        $salesStaff = Role::firstOrCreate(['name' => 'sales_staff', 'guard_name' => 'web']);
        $salesStaff->givePermissionTo([
            'view_products',
            'view_orders',
            'view_inquiries', 'update_inquiries',
        ]);

        $contentEditor = Role::firstOrCreate(['name' => 'content_editor', 'guard_name' => 'web']);
        $contentEditor->givePermissionTo([
            'view_products', 'create_products', 'update_products',
            'view_categories', 'create_categories', 'update_categories',
            'view_brands', 'create_brands', 'update_brands',
            'view_services', 'create_services', 'update_services',
            'view_portfolios', 'create_portfolios', 'update_portfolios',
            'view_media', 'upload_media', 'delete_media',
        ]);

        $user = User::firstOrCreate(
            ['email' => 'admin@kmdecor.com'],
            [
                'name' => 'Super Admin',
                'phone' => null,
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $user->assignRole('super_admin');
    }
}
