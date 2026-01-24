<?php

namespace App\Contracts;

use App\Models\Ticket;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkReport;
use App\Models\JobTemplate;

interface NotificationChannelInterface
{
    /**
     * Get the channel name
     */
    public function getChannelName(): string;

    /**
     * Check if channel is properly configured
     */
    public function isConfigured(): bool;

    /**
     * Send notification for new ticket creation
     * @return array ['group' => bool|null, 'staff' => bool|null]
     */
    public function sendNewTicketNotification(Ticket $ticket): array;

    /**
     * Send notification to User (Reporter) when ticket is created
     */
    public function sendTicketCreatedUser(Ticket $ticket): void;

    /**
     * Send notification for ticket status update
     */
    public function sendTicketStatusUpdateNotification(Ticket $ticket, string $oldStatus): void;

    /**
     * Send notification for new ticket reply
     */
    public function sendTicketReplyNotification(Ticket $ticket, string $replyContent, string $replierName): void;

    /**
     * Send notification for ticket staff assignment changes
     */
    public function sendTicketAssignmentNotification(Ticket $ticket, array $oldAssignedStaff): void;

    /**
     * Send alert for unassigned ticket (1 hour+)
     */
    public function sendUnassignedTicketAlert(Ticket $ticket): void;

    /**
     * Send notification for new work order (SPK) creation
     * @return bool|null true if sent, false if failed, null if not attempted
     */
    public function sendWorkOrderNotification(WorkOrder $workOrder): ?bool;

    /**
     * Send notification for Work Report Created (to Admin/Group)
     */
    public function sendWorkReportNotification(WorkReport $report): void;

    /**
     * Send notification when Work Order is completed (to User)
     */
    public function sendWorkOrderCompletedUser(WorkOrder $workOrder): void;

    /**
     * Send notification when Work Order is marked as "done" (to Group and Staff)
     */
    public function sendWorkOrderDoneNotification(WorkOrder $workOrder): void;

    /**
     * Send SLA Warning
     */
    public function sendSLAWarning(Ticket $ticket, int $hours): void;

    /**
     * Send Routine Maintenance Reminder
     */
    public function sendRoutineMaintenanceReminder(User $staff, JobTemplate $job): void;

    /**
     * Send test message to specific recipient
     */
    public function sendTestMessage(string $recipient, string $message): bool;

    /**
     * Send test message to group
     */
    public function sendTestMessageToGroup(string $message): bool;
}
