<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{

    private $permissions = [
        'dashboard' => [
            'menu',
            'view',
            'view-metrics',
            'view-charts',
            'view-staff-rankings',
            'view-trends'
        ],

        'user' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete'
        ],

        'branch' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete'
        ],

        'job-template' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete'
        ],

        'ticket' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete',
            'update-status'
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
            'update-status'
        ],

        'work-report' => [
            'menu',
            'list',
            'create',
            'edit',
            'delete'
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
            'delete'
        ],

        'utility-reading' => [
            'list',
            'create',
            'edit',
            'delete'
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
