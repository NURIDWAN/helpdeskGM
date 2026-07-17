<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{

    private $permissions = [
        // System-level permissions (tidak terkait modul tertentu)
        'system' => [
            'admin-panel-access', // Menentukan user bisa akses Admin Layout
        ],

        'dashboard' => [
            'menu',
            'view',
            'view-metrics',
            'view-charts',
            'view-staff-rankings',
            'view-trends',
            'view-all' // Allow viewing unlimited data
        ],

        'user' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete'
        ],

        'role' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete'
        ],

        'whatsapp-setting' => [
            'menu',
            'list',
            'edit'
        ],

        'user-activity' => [
            'menu',
            'list'
        ],

        'branch' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete',
            'view-all'
        ],

        'job-template' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete',
            'view-all'
        ],

        'ticket' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete',
            'update-status',
            'view-all'
        ],

        'ticket-category' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete'
        ],

        'ticket-reply' => [
            'list',
            'create',
            'edit',
            'delete'
        ],

        'ticket-attachment' => [
            'list',
            'create',
            'delete'
        ],

        'work-order' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete',
            'update-status',
            'view-all'
        ],

        'work-report' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete',
            'view-all'
        ],

        'work-report-attachment' => [
            'list',
            'create',
            'delete'
        ],

        'daily-record' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete',
            'view-all'
        ],

        'utility-reading' => [
            'list',
            'create',
            'edit',
            'delete',
            'view-all'
        ],

        'electricity-meter' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete'
        ],

        'electricity-reading' => [
            'list',
            'create',
            'edit',
            'delete'
        ],

        'activity-log' => [
            'menu',
            'list',
        ],

        'form-permintaan' => [
            'menu',
            'create',
            'list',
            'confirm',
            'view-all',
            'review',
            'reject',
            'edit',
            'delete',
        ],
    ];


    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->permissions as $key => $value) {
            foreach ($value as $permission) {
                Permission::firstOrCreate([
                    'name' => $key . '-' . $permission,
                    'guard_name' => 'sanctum'
                ]);
            }
        }
    }
}
