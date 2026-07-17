<?php

namespace App\Services;

use App\Contracts\NotificationChannelInterface;
use App\Models\JobTemplate;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsAppSetting;
use App\Models\WorkOrder;
use App\Models\WorkReport;
use App\Models\FormPermintaan;
use Illuminate\Support\Facades\Log;

/**
 * NotificationService - Facade/Manager for notification channels
 * 
 * This service acts as a central point for all notifications.
 * It routes notifications to the active channel (WhatsApp or Telegram)
 * based on the configuration.
 */
class NotificationService
{
    private ?NotificationChannelInterface $channel = null;
    private string $activeChannel;

    public function __construct()
    {
        $this->activeChannel = WhatsAppSetting::getValue('notification_channel') ?: 'whatsapp';
        $this->initializeChannel();
    }

    /**
     * Initialize the active notification channel
     */
    private function initializeChannel(): void
    {
        $this->channel = match ($this->activeChannel) {
            'telegram' => app(TelegramNotificationService::class),
            default => app(WhatsAppNotificationService::class),
        };
    }

    /**
     * Get the active channel instance
     */
    public function getChannel(): ?NotificationChannelInterface
    {
        return $this->channel;
    }

    /**
     * Get the active channel name
     */
    public function getActiveChannelName(): string
    {
        return $this->activeChannel;
    }

    /**
     * Check if the active channel is configured
     */
    public function isConfigured(): bool
    {
        return $this->channel?->isConfigured() ?? false;
    }

    /**
     * Get a specific channel by name (useful for testing)
     */
    public function getChannelByName(string $channelName): ?NotificationChannelInterface
    {
        return match ($channelName) {
            'telegram' => app(TelegramNotificationService::class),
            'whatsapp' => app(WhatsAppNotificationService::class),
            default => null,
        };
    }

    // ========================================
    // PROXY METHODS - Delegate to active channel
    // ========================================

    /**
     * Send notification for new ticket creation
     * @return array ['group' => bool|null, 'staff' => bool|null]
     */
    public function sendNewTicketNotification(Ticket $ticket): array
    {
        if (!$this->channel) {
            Log::warning('No notification channel configured');
            return ['group' => null, 'staff' => null];
        }

        return $this->channel->sendNewTicketNotification($ticket);
    }

    /**
     * Send notification to User (Reporter) when ticket is created
     */
    public function sendTicketCreatedUser(Ticket $ticket): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->sendTicketCreatedUser($ticket);
    }

    /**
     * Send notification for ticket status update
     */
    public function sendTicketStatusUpdateNotification(Ticket $ticket, string $oldStatus): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->sendTicketStatusUpdateNotification($ticket, $oldStatus);
    }

    /**
     * Send notification for new ticket reply
     */
    public function sendTicketReplyNotification(Ticket $ticket, string $replyContent, string $replierName): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->sendTicketReplyNotification($ticket, $replyContent, $replierName);
    }

    /**
     * Send notification for ticket staff assignment changes
     */
    public function sendTicketAssignmentNotification(Ticket $ticket, array $oldAssignedStaff): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->sendTicketAssignmentNotification($ticket, $oldAssignedStaff);
    }

    /**
     * Send alert for unassigned ticket (1 hour+)
     */
    public function sendUnassignedTicketAlert(Ticket $ticket): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->sendUnassignedTicketAlert($ticket);
    }

    /**
     * Send notification for new work order (SPK) creation
     * @return bool|null true if sent, false if failed, null if not attempted
     */
    public function sendWorkOrderNotification(WorkOrder $workOrder): ?bool
    {
        if (!$this->channel) {
            return null;
        }

        return $this->channel->sendWorkOrderNotification($workOrder);
    }

    /**
     * Send notification for new form permintaan creation
     */
    public function sendFormPermintaanNotification(FormPermintaan $formPermintaan): ?bool
    {
        if (!$this->channel) {
            return null;
        }

        return $this->channel->sendFormPermintaanNotification($formPermintaan);
    }

    /**
     * Send notification for Work Report Created (to Admin/Group)
     */
    public function sendWorkReportNotification(WorkReport $report): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->sendWorkReportNotification($report);
    }

    /**
     * Send notification when Work Order is completed (to User)
     */
    public function sendWorkOrderCompletedUser(WorkOrder $workOrder): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->sendWorkOrderCompletedUser($workOrder);
    }

    /**
     * Send notification when Work Order is marked as "done" (to Group and Staff)
     */
    public function sendWorkOrderDoneNotification(WorkOrder $workOrder): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->sendWorkOrderDoneNotification($workOrder);
    }

    /**
     * Send SLA Warning
     */
    public function sendSLAWarning(Ticket $ticket, int $hours): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->sendSLAWarning($ticket, $hours);
    }

    /**
     * Send Routine Maintenance Reminder
     */
    public function sendRoutineMaintenanceReminder(User $staff, JobTemplate $job): void
    {
        if (!$this->channel) {
            return;
        }

        $this->channel->sendRoutineMaintenanceReminder($staff, $job);
    }

    /**
     * Send test message to specific recipient
     */
    public function sendTestMessage(string $recipient, string $message): bool
    {
        if (!$this->channel) {
            return false;
        }

        return $this->channel->sendTestMessage($recipient, $message);
    }

    /**
     * Send test message to group
     */
    public function sendTestMessageToGroup(string $message): bool
    {
        if (!$this->channel) {
            return false;
        }

        return $this->channel->sendTestMessageToGroup($message);
    }

    /**
     * Send test message using a specific channel
     */
    public function sendTestMessageViaChannel(string $channelName, string $recipient, string $message): bool
    {
        $channel = $this->getChannelByName($channelName);
        if (!$channel) {
            return false;
        }

        return $channel->sendTestMessage($recipient, $message);
    }

    /**
     * Send test message to group using a specific channel
     */
    public function sendTestMessageToGroupViaChannel(string $channelName, string $message): bool
    {
        $channel = $this->getChannelByName($channelName);
        if (!$channel) {
            return false;
        }

        return $channel->sendTestMessageToGroup($message);
    }
}
