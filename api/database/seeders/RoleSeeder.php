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
        Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'sanctum'
        ]);

        $staff = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => 'sanctum'
        ]);

        $user = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'sanctum'
        ]);

        // Assign baseline permissions per role (admin gets all in RolePermissionSeeder)
        $staffPermissions = [
            // tickets
            'ticket-menu',
            'ticket-list',
            'ticket-edit',
            // ticket replies
            'ticket-reply-list',
            'ticket-reply-create',
            'ticket-reply-edit',
            'ticket-reply-delete',
            // ticket attachments
            'ticket-attachment-list',
            'ticket-attachment-create',
            'ticket-attachment-delete',
        ];

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

        $staff->syncPermissions(Permission::whereIn('name', $staffPermissions)->get());
        $user->syncPermissions(Permission::whereIn('name', $userPermissions)->get());
    }
}
