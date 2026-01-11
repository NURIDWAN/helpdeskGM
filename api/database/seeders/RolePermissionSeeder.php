<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        $admin = Role::where('name', 'admin')->first();
        $admin->syncPermissions(Permission::all());

        $staff = Role::where('name', 'staff')->first();
        $staff->syncPermissions(
            Permission::where(function ($q) {
                $q->where('name', 'like', 'ticket-%')
                    ->orWhere('name', 'like', 'work-order-%')
                    ->orWhere('name', 'like', 'work-report-%')
                    ->orWhere('name', 'branch-list')
                    ->orWhere('name', 'job-template-list')
                    ->orWhere('name', 'dashboard-menu')
                    ->orWhere('name', 'dashboard-view')
                    ->orWhere('name', 'dashboard-view-metrics')
                    ->orWhere('name', 'dashboard-view-charts')
                    ->orWhere('name', 'dashboard-view-trends');
            })
                // Staff should NOT have these permissions (they should only see assigned tickets)
                ->whereNotIn('name', [
                    'ticket-create',
                    'ticket-delete',
                    'ticket-edit',
                    'ticket-view-all',
                    'work-order-create',
                    'work-order-delete',
                    'work-order-edit',
                    'work-order-view-all',
                    'work-report-view-all',
                    'dashboard-view-staff-rankings',
                ])
                ->get()
        );

        $user = Role::where('name', 'user')->first();
        $user->syncPermissions(
            Permission::where(function ($q) {
                $q->where('name', 'like', 'ticket-%')
                    ->orWhere('name', 'like', 'branch-list')
                    ->orWhere('name', 'like', 'daily-record-%')
                    ->orWhere('name', 'like', 'utility-reading-%')
                    ->orWhere('name', 'like', 'electricity-meter-list')
                    ->orWhere('name', 'like', 'electricity-reading-%');
            })
                // User should NOT have these permissions (they should only see their own data)
                ->whereNotIn('name', [
                    'ticket-view-all',
                    'ticket-delete',
                    'ticket-edit',
                    'daily-record-view-all',
                    'utility-reading-view-all',
                ])
                ->get()
        );
    }
}

