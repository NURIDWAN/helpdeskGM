<?php

/**
 * Role Presets Configuration
 *
 * Mendefinisikan preset role yang tersedia untuk mempercepat pembuatan role baru.
 * Preset ini mirror dari frontend permissionConfig.js rolePresets.
 */

return [
    'staff' => [
        'label' => 'Staff',
        'description' => 'Akses operasional tiket, SPK, dan laporan pekerjaan',
        'permissions' => [
            // admin panel access
            'system-admin-panel-access',
            // dashboard (basic view)
            'dashboard-menu',
            'dashboard-view',
            // branches (readonly for dropdowns)
            'branch-list',
            // ticket categories (for ticket forms)
            'ticket-category-list',
            // job templates (for work orders/reports)
            'job-template-list',
            // users (readonly for dropdowns/assign)
            'user-list',
            // tickets
            'ticket-menu',
            'ticket-list',
            'ticket-create',
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
            // form permintaan
            'form-permintaan-menu',
            'form-permintaan-list',
            'form-permintaan-confirm',
            'form-permintaan-view-all',
        ],
    ],

    'user' => [
        'label' => 'User',
        'description' => 'Akses dasar tiket dan laporan harian',
        'permissions' => [
            // ticket categories (for dropdown in ticket form)
            'ticket-category-list',
            // tickets
            'ticket-menu',
            'ticket-list',
            'ticket-create',
            'ticket-update-status',
            // ticket replies
            'ticket-reply-list',
            'ticket-reply-create',
            // ticket attachments
            'ticket-attachment-list',
            'ticket-attachment-create',
            'ticket-attachment-delete',
            // resources needed for forms
            'branch-list',
            'user-list',
            'electricity-meter-list',
            // daily reports
            'daily-record-menu',
            'daily-record-list',
            'daily-record-create',
            'daily-record-edit',
            'utility-reading-list',
            'utility-reading-create',
            'utility-reading-edit',
            'electricity-reading-list',
            'electricity-reading-create',
            'electricity-reading-edit',
        ],
    ],

    'admin' => [
        'label' => 'Admin',
        'description' => 'Akses semua fitur kecuali manajemen role dan pengaturan WhatsApp',
        'permissions' => [
            // System
            'system-admin-panel-access',
            // Dashboard
            'dashboard-menu', 'dashboard-view', 'dashboard-view-metrics', 'dashboard-view-charts',
            'dashboard-view-staff-rankings', 'dashboard-view-trends', 'dashboard-view-all',
            // User management
            'user-menu', 'user-list', 'user-create', 'user-edit', 'user-delete',
            // Role (view only)
            'role-menu', 'role-list',
            // Branch
            'branch-menu', 'branch-list', 'branch-create', 'branch-edit', 'branch-delete', 'branch-view-all',
            // Job Template
            'job-template-menu', 'job-template-list', 'job-template-create', 'job-template-edit', 'job-template-delete', 'job-template-view-all',
            // Ticket Category
            'ticket-category-menu', 'ticket-category-list', 'ticket-category-create', 'ticket-category-edit', 'ticket-category-delete',
            // Tickets
            'ticket-menu', 'ticket-list', 'ticket-create', 'ticket-edit', 'ticket-delete', 'ticket-update-status', 'ticket-view-all',
            'ticket-reply-list', 'ticket-reply-create', 'ticket-reply-edit', 'ticket-reply-delete',
            'ticket-attachment-list', 'ticket-attachment-create', 'ticket-attachment-delete',
            // Form Permintaan
            'form-permintaan-menu', 'form-permintaan-list', 'form-permintaan-create', 'form-permintaan-confirm', 'form-permintaan-view-all',
            // Work Order
            'work-order-menu', 'work-order-list', 'work-order-create', 'work-order-edit', 'work-order-delete', 'work-order-update-status', 'work-order-view-all',
            // Work Report
            'work-report-menu', 'work-report-list', 'work-report-create', 'work-report-edit', 'work-report-delete', 'work-report-view-all',
            'work-report-attachment-list', 'work-report-attachment-create', 'work-report-attachment-delete',
            // Daily Record
            'daily-record-menu', 'daily-record-list', 'daily-record-create', 'daily-record-edit', 'daily-record-delete', 'daily-record-view-all',
            'utility-reading-list', 'utility-reading-create', 'utility-reading-edit', 'utility-reading-delete', 'utility-reading-view-all',
            // Electricity
            'electricity-meter-menu', 'electricity-meter-list', 'electricity-meter-create', 'electricity-meter-edit', 'electricity-meter-delete',
            'electricity-reading-list', 'electricity-reading-create', 'electricity-reading-edit', 'electricity-reading-delete',
        ],
    ],
];
