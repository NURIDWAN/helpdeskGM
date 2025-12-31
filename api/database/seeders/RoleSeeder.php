<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create superadmin role with ALL permissions
        $superadmin = Role::firstOrCreate([
            'name' => 'superadmin',
            'guard_name' => 'sanctum'
        ]);

        // Create admin role (all except role management)
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'sanctum'
        ]);

        // Create staff role
        $staff = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => 'sanctum'
        ]);

        // Create user role (basic ticket access)
        $user = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'sanctum'
        ]);

        // Get all permissions
        $allPermissions = Permission::all();

        // Superadmin gets ALL permissions
        $superadmin->syncPermissions($allPermissions);

        // Admin gets all permissions EXCEPT role-*, whatsapp-setting-*, user-activity-*
        $adminPermissions = $allPermissions->filter(function ($permission) {
            return !str_starts_with($permission->name, 'role-') &&
                !str_starts_with($permission->name, 'whatsapp-setting-') &&
                !str_starts_with($permission->name, 'user-activity-');
        });
        $admin->syncPermissions($adminPermissions);

        // Staff permissions - operational access
        $staffPermissions = [
            // tickets
            'ticket-menu',
            'ticket-list',
            'ticket-edit',
            'ticket-update-status',
            // ticket replies
            'ticket-reply-list',
            'ticket-reply-create',
            'ticket-reply-edit',
            'ticket-reply-delete',
            // ticket attachments
            'ticket-attachment-list',
            'ticket-attachment-create',
            'ticket-attachment-delete',
            // work orders
            'work-order-menu',
            'work-order-list',
            'work-order-update-status',
            // work reports
            'work-report-menu',
            'work-report-list',
            'work-report-create',
            'work-report-edit',
            'work-report-attachment-list',
            'work-report-attachment-create',
            'work-report-attachment-delete',
            // daily records
            'daily-record-menu',
            'daily-record-list',
            'daily-record-create',
            'daily-record-edit',
            // utility readings
            'utility-reading-list',
            'utility-reading-create',
            'utility-reading-edit',
            // electricity readings
            'electricity-reading-list',
            'electricity-reading-create',
            'electricity-reading-edit',
        ];
        $staff->syncPermissions(Permission::whereIn('name', $staffPermissions)->get());

        // User permissions - basic ticket access only
        $userPermissions = [
            // tickets
            'ticket-menu',
            'ticket-list',
            'ticket-create',
            // ticket replies
            'ticket-reply-list',
            'ticket-reply-create',
            // ticket attachments
            'ticket-attachment-list',
            'ticket-attachment-create',
            'ticket-attachment-delete',
        ];
        $user->syncPermissions(Permission::whereIn('name', $userPermissions)->get());
    }
}
