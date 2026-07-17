<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketReplyController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketCategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\WorkReportController;
use App\Http\Controllers\WorkReportAttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobTemplateController;
use App\Http\Controllers\DailyRecordController;
use App\Http\Controllers\UtilityReadingController;
use App\Http\Controllers\ElectricityMeterController;
use App\Http\Controllers\ElectricityReadingController;
use App\Http\Controllers\FormPermintaanController;
use App\Http\Controllers\BrowserNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Telegram webhook (public, no auth required)
    Route::post('telegram/webhook', [\App\Http\Controllers\TelegramBotController::class, 'webhook']);

    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::put('me', [AuthController::class, 'updateProfile']);
            Route::post('me/photo', [AuthController::class, 'uploadPhoto']);
            Route::delete('me/photo', [AuthController::class, 'deletePhoto']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::apiResource('branches', BranchController::class);
        Route::get('branches/all/paginated', [BranchController::class, 'getAllPaginated']);

        // Ticket Categories
        Route::apiResource('ticket-categories', TicketCategoryController::class);

        Route::get('tickets/all/paginated', [TicketController::class, 'getAllPaginated']);
        Route::get('tickets/code/{code}', [TicketController::class, 'showByCode'])->where('code', '.*');
        Route::get('tickets/export/pdf', [TicketController::class, 'exportPdf']);
        Route::get('tickets/export/excel', [TicketController::class, 'exportExcel']);
        Route::apiResource('tickets', TicketController::class);
        Route::put('tickets/{id}/close', [TicketController::class, 'closeTicket']);

        Route::get('tickets/{ticketId}/attachments', [TicketAttachmentController::class, 'index']);
        Route::post('tickets/{ticketId}/attachments', [TicketAttachmentController::class, 'store']);
        Route::delete('tickets/{ticketId}/attachments/{id}', [TicketAttachmentController::class, 'destroy']);

        Route::get('tickets/{ticketId}/replies', [TicketReplyController::class, 'index']);
        Route::post('tickets/{ticketId}/replies', [TicketReplyController::class, 'store']);
        Route::get('tickets/{ticketId}/replies/{id}', [TicketReplyController::class, 'show']);
        Route::put('tickets/{ticketId}/replies/{id}', [TicketReplyController::class, 'update']);
        Route::delete('tickets/{ticketId}/replies/{id}', [TicketReplyController::class, 'destroy']);

        Route::apiResource('users', UserController::class);
        Route::get('users/all/paginated', [UserController::class, 'getAllPaginated']);

        Route::apiResource('work-orders', WorkOrderController::class);
        Route::get('work-orders/all/paginated', [WorkOrderController::class, 'getAllPaginated']);
        Route::get('work-orders/ticket/{ticketId}', [WorkOrderController::class, 'getByTicketId']);
        Route::get('work-orders/{id}/pdf', [WorkOrderController::class, 'downloadPdf']);

        Route::apiResource('work-reports', WorkReportController::class);
        Route::get('work-reports/all/paginated', [WorkReportController::class, 'getAllPaginated']);
        Route::get('work-reports/export/pdf', [WorkReportController::class, 'exportPdf']);
        Route::get('work-reports/export/excel', [WorkReportController::class, 'exportExcel']);

        Route::get('work-reports/{workReportId}/attachments', [WorkReportAttachmentController::class, 'index']);
        Route::post('work-reports/{workReportId}/attachments', [WorkReportAttachmentController::class, 'store']);
        Route::delete('work-reports/{workReportId}/attachments/{id}', [WorkReportAttachmentController::class, 'destroy']);

        Route::get('daily-records/all/paginated', [DailyRecordController::class, 'getAllPaginated']);
        Route::get('daily-records/export/pdf', [DailyRecordController::class, 'exportPdf']);
        Route::get('daily-records/export/excel', [DailyRecordController::class, 'exportExcel']);
        Route::get('daily-records/previous-readings', [DailyRecordController::class, 'getPreviousReadings']);
        Route::get('daily-records/report/daily-usage', [DailyRecordController::class, 'getDailyUsageReport']);
        Route::get('daily-records/report/daily-usage/export', [DailyRecordController::class, 'exportDailyUsageReport']);
        Route::get('daily-records/report/daily-usage/export/pdf', [DailyRecordController::class, 'exportDailyUsageReportPdf']);
        Route::post('daily-records/report/daily-usage/reset', [DailyRecordController::class, 'resetDailyUsageReport']);
        Route::get('daily-records/report-dates', [DailyRecordController::class, 'getReportDates']);

        Route::apiResource('daily-records', DailyRecordController::class);

        Route::apiResource('utility-readings', UtilityReadingController::class);
        Route::get('utility-readings/all/paginated', [UtilityReadingController::class, 'getAllPaginated']);
        Route::get('utility-readings/export/pdf', [UtilityReadingController::class, 'exportPdf']);
        Route::get('utility-readings/export/excel', [UtilityReadingController::class, 'exportExcel']);

        Route::get('daily-records/{dailyRecordId}/utility-readings', [UtilityReadingController::class, 'index']);
        Route::post('daily-records/{dailyRecordId}/utility-readings', [UtilityReadingController::class, 'store']);

        Route::apiResource('job-templates', JobTemplateController::class);
        Route::get('job-templates/all/paginated', [JobTemplateController::class, 'getAllPaginated']);
        Route::post('job-templates/{id}/branches', [JobTemplateController::class, 'assignBranches']);
        Route::delete('job-templates/{id}/branches/{branchId}', [JobTemplateController::class, 'removeBranch']);

        // Electricity Meters routes (Multi-meter support)
        Route::apiResource('electricity-meters', ElectricityMeterController::class);
        Route::get('electricity-meters/all/paginated', [ElectricityMeterController::class, 'getAllPaginated']);
        Route::get('branches/{branchId}/electricity-meters', [ElectricityMeterController::class, 'getByBranch']);

        // Electricity Readings routes (Multi-meter WBP/LWBP)
        Route::apiResource('electricity-readings', ElectricityReadingController::class);
        Route::get('electricity-readings/all/paginated', [ElectricityReadingController::class, 'getAllPaginated']);
        Route::get('electricity-readings/report/multi-meter', [ElectricityReadingController::class, 'getMultiMeterReport']);
        Route::get('daily-records/{dailyRecordId}/electricity-readings', [ElectricityReadingController::class, 'index']);
        Route::post('daily-records/{dailyRecordId}/electricity-readings', [ElectricityReadingController::class, 'store']);
        Route::post('daily-records/{dailyRecordId}/electricity-readings/multiple', [ElectricityReadingController::class, 'storeMultiple']);

        // Dashboard routes
        Route::get('dashboard/metrics', [DashboardController::class, 'getMetrics']);
        Route::get('dashboard/status-distribution', [DashboardController::class, 'getStatusDistribution']);
        Route::get('dashboard/tickets-per-branch', [DashboardController::class, 'getTicketsPerBranch']);
        Route::get('dashboard/top-staff-resolved', [DashboardController::class, 'getTopStaffResolved']);
        Route::get('dashboard/fastest-staff', [DashboardController::class, 'getFastestStaff']);
        Route::get('dashboard/tickets-trend', [DashboardController::class, 'getTicketsTrend']);
        Route::get('dashboard/staff-reports-trend', [DashboardController::class, 'getStaffReportsTrend']);
        Route::get('dashboard/all', [DashboardController::class, 'getAllData']);
        Route::get('dashboard/top-outlet-usage', [DashboardController::class, 'getTopOutletUsage']);

        // Browser notification routes
        Route::get('browser-notifications', [BrowserNotificationController::class, 'index']);
        Route::get('browser-notifications/vapid-public-key', [BrowserNotificationController::class, 'vapidPublicKey']);
        Route::post('browser-notifications/subscribe', [BrowserNotificationController::class, 'subscribe']);
        Route::delete('browser-notifications/subscribe', [BrowserNotificationController::class, 'unsubscribe']);

        // Role Management routes
        Route::get('roles/matrix', [\App\Http\Controllers\RoleController::class, 'matrix']);
        Route::get('roles/presets', [\App\Http\Controllers\RoleController::class, 'presets']);
        Route::apiResource('roles', \App\Http\Controllers\RoleController::class);
        Route::get('permissions', [\App\Http\Controllers\RoleController::class, 'permissions']);

        // WhatsApp Settings routes
        Route::get('whatsapp-settings', [\App\Http\Controllers\WhatsAppSettingController::class, 'index']);
        Route::put('whatsapp-settings', [\App\Http\Controllers\WhatsAppSettingController::class, 'updateSettings']);
        Route::get('whatsapp-templates', [\App\Http\Controllers\WhatsAppSettingController::class, 'getTemplates']);
        Route::put('whatsapp-templates/{id}', [\App\Http\Controllers\WhatsAppSettingController::class, 'updateTemplate']);
        Route::get('whatsapp-placeholders/{type}', [\App\Http\Controllers\WhatsAppSettingController::class, 'getPlaceholders']);
        Route::post('whatsapp-test', [\App\Http\Controllers\WhatsAppSettingController::class, 'testSend']);
        Route::post('whatsapp-test-group', [\App\Http\Controllers\WhatsAppSettingController::class, 'testSendGroup']);

        // Notification Settings (Channel Switching)
        Route::get('notification-settings', [\App\Http\Controllers\WhatsAppSettingController::class, 'getNotificationSettings']);
        Route::put('notification-settings', [\App\Http\Controllers\WhatsAppSettingController::class, 'updateNotificationSettings']);
        Route::post('telegram-test', [\App\Http\Controllers\WhatsAppSettingController::class, 'testTelegramSend']);
        Route::post('telegram-test-group', [\App\Http\Controllers\WhatsAppSettingController::class, 'testTelegramSendGroup']);

        // Telegram Bot (User Profile Integration)
        Route::get('telegram/status', [\App\Http\Controllers\TelegramBotController::class, 'status']);
        Route::post('telegram/generate-token', [\App\Http\Controllers\TelegramBotController::class, 'generateToken']);
        Route::post('telegram/disconnect', [\App\Http\Controllers\TelegramBotController::class, 'disconnect']);
        Route::post('telegram/set-webhook', [\App\Http\Controllers\TelegramBotController::class, 'setWebhook']);
        Route::get('telegram/webhook-info', [\App\Http\Controllers\TelegramBotController::class, 'getWebhookInfo']);

        // Telegram Login Widget
        Route::post('telegram/widget-login', [\App\Http\Controllers\TelegramLoginWidgetController::class, 'verifyAndLink']);
        Route::get('telegram/bot-info', [\App\Http\Controllers\TelegramLoginWidgetController::class, 'getBotInfo']);

        // Job Schedule routes (Calendar)
        Route::get('job-schedules', [\App\Http\Controllers\JobScheduleController::class, 'getSchedule']);
        Route::get('job-schedules/today', [\App\Http\Controllers\JobScheduleController::class, 'getTodaysSummary']);

        // User Activity Monitoring routes
        Route::get('user-activity', [\App\Http\Controllers\UserActivityController::class, 'index']);
        Route::get('user-activity/statistics', [\App\Http\Controllers\UserActivityController::class, 'statistics']);

        // Activity Log routes
        Route::get('activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index']);
        Route::get('activity-logs/statistics', [\App\Http\Controllers\ActivityLogController::class, 'statistics']);
        Route::get('activity-logs/{id}', [\App\Http\Controllers\ActivityLogController::class, 'show']);

        // Form Permintaan routes
        Route::get('form-permintaan', [FormPermintaanController::class, 'index']);
        Route::post('form-permintaan', [FormPermintaanController::class, 'store']);
        Route::get('form-permintaan/export/pdf', [FormPermintaanController::class, 'exportPdf']);
        Route::get('form-permintaan/export/excel', [FormPermintaanController::class, 'exportExcel']);
        Route::get('form-permintaan/{id}/pdf', [FormPermintaanController::class, 'downloadPdf']);
        Route::get('form-permintaan/{id}', [FormPermintaanController::class, 'show']);
        Route::put('form-permintaan/{id}', [FormPermintaanController::class, 'update']);
        Route::put('form-permintaan/{id}/confirm', [FormPermintaanController::class, 'confirm']);
        Route::put('form-permintaan/{id}/status', [FormPermintaanController::class, 'updateStatus']);
        Route::put('form-permintaan/{id}/review', [FormPermintaanController::class, 'review']);
        Route::put('form-permintaan/{id}/reject', [FormPermintaanController::class, 'reject']);
        Route::delete('form-permintaan/{id}', [FormPermintaanController::class, 'destroy']);
        Route::post('form-permintaan/{id}/attachments', [FormPermintaanController::class, 'uploadAttachment']);
        Route::get('form-permintaan/{id}/attachments/{attachmentId}/download', [FormPermintaanController::class, 'downloadAttachment']);
        Route::delete('form-permintaan/{id}/attachments/{attachmentId}', [FormPermintaanController::class, 'deleteAttachment']);
    });
});
