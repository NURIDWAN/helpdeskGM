<?php

/**
 * Permission Features Configuration
 *
 * Mapping feature group → permission names.
 * Mirrors the frontend permissionConfig.js structure for backend matrix calculations.
 */

return [
    'system' => [
        'label' => 'Akses Sistem',
        'permissions' => [
            'system-admin-panel-access',
        ],
    ],

    'dashboard' => [
        'label' => 'Dashboard',
        'permissions' => [
            'dashboard-menu',
            'dashboard-view',
            'dashboard-view-metrics',
            'dashboard-view-charts',
            'dashboard-view-staff-rankings',
            'dashboard-view-trends',
            'dashboard-view-all',
        ],
    ],

    'user' => [
        'label' => 'Manajemen User',
        'permissions' => [
            'user-menu',
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
        ],
    ],

    'role' => [
        'label' => 'Manajemen Role',
        'permissions' => [
            'role-menu',
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
        ],
    ],

    'branch' => [
        'label' => 'Manajemen Cabang',
        'permissions' => [
            'branch-menu',
            'branch-list',
            'branch-create',
            'branch-edit',
            'branch-delete',
            'branch-view-all',
        ],
    ],

    'jobTemplate' => [
        'label' => 'Template Job',
        'permissions' => [
            'job-template-menu',
            'job-template-list',
            'job-template-create',
            'job-template-edit',
            'job-template-delete',
            'job-template-view-all',
        ],
    ],

    'ticket' => [
        'label' => 'Manajemen Tiket',
        'permissions' => [
            // Core ticket
            'ticket-menu',
            'ticket-list',
            'ticket-create',
            'ticket-edit',
            'ticket-delete',
            'ticket-update-status',
            'ticket-view-all',
            // Ticket replies
            'ticket-reply-list',
            'ticket-reply-create',
            'ticket-reply-edit',
            'ticket-reply-delete',
            // Ticket attachments
            'ticket-attachment-list',
            'ticket-attachment-create',
            'ticket-attachment-delete',
        ],
    ],

    'ticketCategory' => [
        'label' => 'Kategori Tiket',
        'permissions' => [
            'ticket-category-menu',
            'ticket-category-list',
            'ticket-category-create',
            'ticket-category-edit',
            'ticket-category-delete',
        ],
    ],

    'formPermintaan' => [
        'label' => 'Form Permintaan',
        'permissions' => [
            'form-permintaan-menu',
            'form-permintaan-list',
            'form-permintaan-create',
            'form-permintaan-confirm',
            'form-permintaan-view-all',
        ],
    ],

    'workOrder' => [
        'label' => 'Surat Perintah Kerja',
        'permissions' => [
            'work-order-menu',
            'work-order-list',
            'work-order-create',
            'work-order-edit',
            'work-order-delete',
            'work-order-update-status',
            'work-order-view-all',
        ],
    ],

    'workReport' => [
        'label' => 'Laporan Pekerjaan',
        'permissions' => [
            // Core work report
            'work-report-menu',
            'work-report-list',
            'work-report-create',
            'work-report-edit',
            'work-report-delete',
            'work-report-view-all',
            // Work report attachments
            'work-report-attachment-list',
            'work-report-attachment-create',
            'work-report-attachment-delete',
        ],
    ],

    'dailyRecord' => [
        'label' => 'Laporan Harian',
        'permissions' => [
            // Core daily record
            'daily-record-menu',
            'daily-record-list',
            'daily-record-create',
            'daily-record-edit',
            'daily-record-delete',
            'daily-record-view-all',
            // Utility readings
            'utility-reading-list',
            'utility-reading-create',
            'utility-reading-edit',
            'utility-reading-delete',
            'utility-reading-view-all',
        ],
    ],

    'electricity' => [
        'label' => 'Manajemen Listrik',
        'permissions' => [
            // Electricity meters
            'electricity-meter-menu',
            'electricity-meter-list',
            'electricity-meter-create',
            'electricity-meter-edit',
            'electricity-meter-delete',
            // Electricity readings
            'electricity-reading-list',
            'electricity-reading-create',
            'electricity-reading-edit',
            'electricity-reading-delete',
        ],
    ],

    'whatsappSetting' => [
        'label' => 'Pengaturan WhatsApp',
        'permissions' => [
            'whatsapp-setting-menu',
            'whatsapp-setting-list',
            'whatsapp-setting-edit',
        ],
    ],

    'userActivity' => [
        'label' => 'Monitoring Aktivitas',
        'permissions' => [
            'user-activity-menu',
            'user-activity-list',
        ],
    ],
];
