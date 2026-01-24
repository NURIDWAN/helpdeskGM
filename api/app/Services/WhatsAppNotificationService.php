<?php

namespace App\Services;

use App\Contracts\NotificationChannelInterface;
use App\Models\JobTemplate;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppTemplate;
use App\Models\WorkReport;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService implements NotificationChannelInterface
{
    private string $apiUrl;
    private ?string $token;
    private int $delay;
    private ?string $groupId; // WhatsApp Group ID
    private string $appUrl;

    public function __construct()
    {
        // Load settings from database, fallback to config if not set
        $this->apiUrl = config('services.whatsapp.api_url', 'https://api.fonnte.com/send');

        $this->token = WhatsAppSetting::getValue('token')
            ?: config('services.whatsapp.token');

        $this->delay = (int) (WhatsAppSetting::getValue('delay')
            ?: config('services.whatsapp.delay', 2));

        $this->groupId = WhatsAppSetting::getValue('group_id')
            ?: config('services.whatsapp.group_id');

        $this->appUrl = config('app.frontend_url', config('app.url'));
    }

    /**
     * Send notification for new ticket creation
     * @return array ['group' => bool|null, 'staff' => bool|null]
     */
    public function sendNewTicketNotification(Ticket $ticket): array
    {
        $result = ['group' => null, 'staff' => null];

        try {
            if (!$this->token)
                return $result;

            // Template: ticket_created (for Group)
            $templateType = 'ticket_created';
            $message = $this->buildMessage($templateType, $ticket);

            // Send to Group
            if ($this->groupId) {
                try {
                    $this->sendMessageToGroup($message);
                    $result['group'] = true;
                    Log::info('WhatsApp notification sent to group for new ticket', [
                        'ticket_id' => $ticket->id,
                        'group_id' => $this->groupId
                    ]);
                } catch (\Exception $e) {
                    $result['group'] = false;
                    Log::error('Failed to send WhatsApp to group', [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Send to Assigned Staff
            if ($ticket->assignedStaff && $ticket->assignedStaff->count() > 0) {
                try {
                    $staffRecipients = [];
                    foreach ($ticket->assignedStaff as $staff) {
                        if ($staff->phone_number) {
                            $phone = $this->formatPhoneNumber($staff->phone_number);
                            if ($phone)
                                $staffRecipients[] = $phone;
                        }
                    }

                    if (!empty($staffRecipients)) {
                        $staffMessage = $this->buildMessage('ticket_assigned', $ticket);
                        $this->sendMessage($staffMessage, $staffRecipients);
                        $result['staff'] = true;
                        Log::info('WhatsApp notification sent to staff for new ticket', [
                            'ticket_id' => $ticket->id,
                            'staff_count' => count($staffRecipients)
                        ]);
                    }
                } catch (\Exception $e) {
                    $result['staff'] = false;
                    Log::error('Failed to send WhatsApp to staff', [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification for new ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
            return $result;
        }
    }

    /**
     * Send notification to User (Reporter) when ticket is created
     */
    public function sendTicketCreatedUser(Ticket $ticket): void
    {
        try {
            if (!$this->token || !$ticket->user || !$ticket->user->phone_number)
                return;

            $message = $this->buildMessage('ticket_created_user', $ticket);
            $phone = $this->formatPhoneNumber($ticket->user->phone_number);

            if ($phone) {
                $this->sendMessage($message, [$phone]);
                Log::info('WhatsApp confirmation sent to user', ['ticket_id' => $ticket->id]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp confirmation to user', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification for ticket status update
     */
    public function sendTicketStatusUpdateNotification(Ticket $ticket, string $oldStatus): void
    {
        try {
            if (!$this->token)
                return;

            $status = $ticket->status->value;
            $templateType = match ($status) {
                'closed' => 'ticket_closed',
                'in_progress' => 'ticket_status_progress',
                'resolved' => 'ticket_status_resolved',
                default => 'ticket_status_update'
            };

            $message = $this->buildMessage($templateType, $ticket, [
                'old_status' => $this->getStatusText($oldStatus),
                'new_status' => $this->getStatusText($status)
            ]);

            // Logic for recipients based on status
            Log::info('Processing ticket status update notification', [
                'ticket_id' => $ticket->id,
                'status' => $status,
                'is_closed' => $status === 'closed'
            ]);

            if ($status === 'closed') {
                // Closed -> Send to Group AND User (Creator)
                Log::info('Ticket is being closed, sending notifications', ['ticket_id' => $ticket->id]);

                // 1. Send to Group
                if ($this->groupId) {
                    $this->sendMessageToGroup($message);
                    Log::info('WhatsApp notification sent to group for closed ticket', [
                        'ticket_id' => $ticket->id
                    ]);
                }

                // 2. Send to User (Creator) - personal notification
                if ($ticket->user && $ticket->user->phone_number) {
                    $userMessage = $this->buildMessage('ticket_closed_user', $ticket, [
                        'old_status' => $this->getStatusText($oldStatus),
                        'new_status' => $this->getStatusText($status)
                    ]);
                    $phone = $this->formatPhoneNumber($ticket->user->phone_number);
                    if ($phone) {
                        $this->sendMessage($userMessage, [$phone]);
                        Log::info('WhatsApp notification sent to user for closed ticket', [
                            'ticket_id' => $ticket->id,
                            'user_id' => $ticket->user->id
                        ]);
                    }
                }

                // 3. Send to Assigned Staff - so they know ticket is complete
                if ($ticket->assignedStaff && $ticket->assignedStaff->count() > 0) {
                    $staffRecipients = [];
                    foreach ($ticket->assignedStaff as $staff) {
                        if ($staff->phone_number) {
                            $phone = $this->formatPhoneNumber($staff->phone_number);
                            if ($phone)
                                $staffRecipients[] = $phone;
                        }
                    }

                    if (!empty($staffRecipients)) {
                        $staffMessage = $this->buildMessage('ticket_closed_staff', $ticket, [
                            'old_status' => $this->getStatusText($oldStatus),
                            'new_status' => $this->getStatusText($status)
                        ]);
                        $this->sendMessage($staffMessage, $staffRecipients);
                        Log::info('WhatsApp notification sent to staff for closed ticket', [
                            'ticket_id' => $ticket->id,
                            'staff_count' => count($staffRecipients)
                        ]);
                    }
                }
            } else {
                // Progress/Resolved -> Send to User (Creator)
                $recipients = [];

                // Add Creator
                if ($ticket->user && $ticket->user->phone_number) {
                    $phone = $this->formatPhoneNumber($ticket->user->phone_number);
                    if ($phone)
                        $recipients[] = $phone;
                }

                if (!empty($recipients)) {
                    $this->sendMessage($message, $recipients);
                    Log::info('WhatsApp notification sent for status update', [
                        'ticket_id' => $ticket->id,
                        'status' => $status,
                        'recipients' => $recipients
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification for status update', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification for new ticket reply
     */
    public function sendTicketReplyNotification(Ticket $ticket, string $replyContent, string $replierName): void
    {
        try {
            if (!$this->token)
                return;

            $message = $this->buildMessage('ticket_reply', $ticket, [
                'replier_name' => $replierName,
                'reply_content' => $replyContent
            ]);

            // Recipient: Creator + Assigned Staff (Exclude Admin, Exclude Replier)
            $recipients = $this->getReplyRecipients($ticket, $replierName);

            if (!empty($recipients)) {
                $this->sendMessage($message, $recipients);
                Log::info('WhatsApp notification sent for ticket reply', [
                    'ticket_id' => $ticket->id,
                    'count' => count($recipients)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification for ticket reply', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification for ticket staff assignment changes
     */
    public function sendTicketAssignmentNotification(Ticket $ticket, array $oldAssignedStaff): void
    {
        try {
            if (!$this->token)
                return;

            $recipients = $this->getAssignmentRecipients($ticket, $oldAssignedStaff);

            if (empty($recipients))
                return;

            foreach ($recipients as $recipient) {
                // Personalize message for each staff
                $message = $this->buildMessage('ticket_assigned', $ticket, [
                    'staff_name' => $recipient['name']
                ]);
                $this->sendMessage($message, [$recipient['phone']]);
            }

            Log::info('WhatsApp notification sent for ticket assignment', [
                'ticket_id' => $ticket->id,
                'count' => count($recipients)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification for ticket assignment', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send alert for unassigned ticket (1 hour+)
     */
    public function sendUnassignedTicketAlert(Ticket $ticket): void
    {
        try {
            if (!$this->token)
                return;

            // 1. Alert to User
            $userMessage = $this->buildMessage('ticket_unassigned_user_alert', $ticket);
            if ($ticket->user && $ticket->user->phone_number) {
                $phone = $this->formatPhoneNumber($ticket->user->phone_number);
                if ($phone) {
                    $this->sendMessage($userMessage, [$phone]);
                    Log::info('Unassigned ticket alert sent to user', ['ticket_id' => $ticket->id]);
                }
            }

            // 2. Alert to Admins
            $adminMessage = $this->buildMessage('ticket_unassigned_admin_alert', $ticket);

            // Send to Group (Optional, for visibility)
            if ($this->groupId) {
                $this->sendMessageToGroup($adminMessage);
            }

            // Send to Admins (Direct)
            $recipients = $this->getAdminRecipients();
            if (!empty($recipients)) {
                $this->sendMessage($adminMessage, $recipients);
            }

            Log::info('Unassigned ticket alert sent to admins/group', ['ticket_id' => $ticket->id]);

        } catch (\Exception $e) {
            Log::error('Failed to send unassigned ticket alert', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification for new work order (SPK) creation
     * @return bool|null true if sent, false if failed, null if not attempted
     */
    public function sendWorkOrderNotification(\App\Models\WorkOrder $workOrder): ?bool
    {
        try {
            if (!$this->token)
                return null;

            // Get assigned technician
            $technician = $workOrder->assignedUser;
            if (!$technician || !$technician->phone_number) {
                Log::info('Work order notification skipped - no technician phone', [
                    'work_order_id' => $workOrder->id,
                    'assigned_to' => $workOrder->assigned_to
                ]);
                return null;
            }

            $phone = $this->formatPhoneNumber($technician->phone_number);
            if (!$phone) {
                Log::info('Work order notification skipped - invalid phone format', [
                    'work_order_id' => $workOrder->id,
                    'phone' => $technician->phone_number
                ]);
                return null;
            }

            // Build message
            $ticket = $workOrder->ticket;
            $ticketInfo = $ticket ? "Tiket: {$ticket->code} - " . ($ticket->category ? $ticket->category->name : 'Tiket') : "SPK Standalone";
            $branchName = $ticket && $ticket->branch ? $ticket->branch->name : 'Tidak ditentukan';

            $message = "📋 *SPK BARU* 📋\n\n" .
                "Halo {$technician->name},\n\n" .
                "Anda ditugaskan untuk SPK baru:\n\n" .
                "🔢 No. SPK: {$workOrder->number}\n" .
                "📌 {$ticketInfo}\n" .
                "🏢 Cabang: {$branchName}\n" .
                ($workOrder->description ? "📝 Deskripsi: {$workOrder->description}\n" : "") .
                ($workOrder->damage_unit ? "🔧 Unit: {$workOrder->damage_unit}\n" : "") .
                ($workOrder->contact_person ? "👤 Kontak: {$workOrder->contact_person}\n" : "") .
                ($workOrder->contact_phone ? "📱 HP: {$workOrder->contact_phone}\n" : "") .
                "\nSilakan buka aplikasi untuk detail.\n" .
                $this->appUrl . '/admin/work-order/' . $workOrder->id;

            $this->sendMessage($message, [$phone]);

            Log::info('Work order notification sent to technician', [
                'work_order_id' => $workOrder->id,
                'technician_id' => $technician->id,
                'phone' => $phone
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send work order notification', [
                'work_order_id' => $workOrder->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification for Work Report Created (to Admin/Group)
     */
    public function sendWorkReportNotification(WorkReport $report): void
    {
        try {
            if (!$this->token || !$this->groupId)
                return;

            $workOrder = $report->workOrder;
            $ticket = $workOrder ? $workOrder->ticket : null;

            // Prepare Data for Template
            $data = [
                'ticket_code' => $workOrder ? $workOrder->number : ($report->custom_job ?? '-'),
                'staff_name' => $report->user->name,
                'branch_name' => $report->branch ? $report->branch->name : '-',
                'status' => $report->status->value,
                'description' => $report->description ?? '-',
            ];

            // Manually replace for non-ticket aware buildMessage function, or we create a specific one.
            // But let's try to reuse buildMessage logic or load template directly.
            $template = WhatsAppTemplate::getActiveByType('work_report_created');
            if ($template) {
                $message = $template->renderContent($data);
                $this->sendMessageToGroup($message);
                Log::info('Work Report notification sent to group', ['report_id' => $report->id]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send work report notification', [
                'report_id' => $report->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification when Work Order is completed (to User)
     */
    public function sendWorkOrderCompletedUser(WorkOrder $workOrder): void
    {
        try {
            if (!$this->token)
                return;

            $ticket = $workOrder->ticket;
            if (!$ticket || !$ticket->user || !$ticket->user->phone_number)
                return;

            $message = $this->buildMessage('work_order_completed_user', $ticket, [
                'staff_name' => $workOrder->assignedUser ? $workOrder->assignedUser->name : 'Teknisi',
            ]);

            $phone = $this->formatPhoneNumber($ticket->user->phone_number);
            if ($phone) {
                $this->sendMessage($message, [$phone]);
                Log::info('Work Order completion notification sent to user', ['work_order_id' => $workOrder->id]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send SPK complete notification', [
                'work_order_id' => $workOrder->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification when Work Order is marked as "done" (to Group and Staff)
     */
    public function sendWorkOrderDoneNotification(WorkOrder $workOrder): void
    {
        try {
            if (!$this->token)
                return;

            $ticket = $workOrder->ticket;
            $ticketInfo = $ticket ? "Tiket: {$ticket->code}" : "SPK Standalone";
            $branchName = $ticket && $ticket->branch ? $ticket->branch->name : 'Tidak ditentukan';
            $categoryName = $ticket && $ticket->category ? $ticket->category->name : '-';

            // Get assigned staff names
            $staffNames = [];
            if ($workOrder->assignedStaff && $workOrder->assignedStaff->count() > 0) {
                foreach ($workOrder->assignedStaff as $staff) {
                    $staffNames[] = $staff->name;
                }
            } elseif ($workOrder->assignedUser) {
                $staffNames[] = $workOrder->assignedUser->name;
            }
            $staffNamesStr = !empty($staffNames) ? implode(', ', $staffNames) : 'Belum ditentukan';

            // Build message
            $message = "✅ *SPK SELESAI* ✅\n\n" .
                "No. SPK: {$workOrder->number}\n" .
                "📌 {$ticketInfo}\n" .
                "📋 Kategori: {$categoryName}\n" .
                "🏢 Cabang: {$branchName}\n" .
                "👷 Teknisi: {$staffNamesStr}\n" .
                "🕐 Selesai: " . now()->format('d/m/Y H:i') . "\n\n" .
                "Surat Perintah Kerja telah diselesaikan.";

            // 1. Send to Group
            if ($this->groupId) {
                $this->sendMessageToGroup($message);
                Log::info('Work Order done notification sent to group', [
                    'work_order_id' => $workOrder->id
                ]);
            }

            // 2. Send to Assigned Staff
            $staffRecipients = [];
            if ($workOrder->assignedStaff && $workOrder->assignedStaff->count() > 0) {
                foreach ($workOrder->assignedStaff as $staff) {
                    if ($staff->phone_number) {
                        $phone = $this->formatPhoneNumber($staff->phone_number);
                        if ($phone)
                            $staffRecipients[] = $phone;
                    }
                }
            } elseif ($workOrder->assignedUser && $workOrder->assignedUser->phone_number) {
                $phone = $this->formatPhoneNumber($workOrder->assignedUser->phone_number);
                if ($phone)
                    $staffRecipients[] = $phone;
            }

            if (!empty($staffRecipients)) {
                $staffMessage = "✅ *SPK ANDA TELAH SELESAI* ✅\n\n" .
                    "No. SPK: {$workOrder->number}\n" .
                    "📌 {$ticketInfo}\n" .
                    "📋 Kategori: {$categoryName}\n" .
                    "🏢 Cabang: {$branchName}\n" .
                    "🕐 Selesai: " . now()->format('d/m/Y H:i') . "\n\n" .
                    "Terima kasih atas kerja keras Anda! 🎉";

                $this->sendMessage($staffMessage, $staffRecipients);
                Log::info('Work Order done notification sent to staff', [
                    'work_order_id' => $workOrder->id,
                    'staff_count' => count($staffRecipients)
                ]);
            }

            // 3. Send to User (Ticket Creator)
            if ($ticket && $ticket->user && $ticket->user->phone_number) {
                $userPhone = $this->formatPhoneNumber($ticket->user->phone_number);
                if ($userPhone) {
                    $userMessage = "📋 *SPK SELESAI* 📋\n\n" .
                        "Halo {$ticket->user->name},\n\n" .
                        "Pekerjaan untuk tiket Anda telah selesai:\n\n" .
                        "📌 Kode Tiket: {$ticket->code}\n" .
                        "📋 Kategori: {$categoryName}\n" .
                        "👷 Teknisi: {$staffNamesStr}\n" .
                        "🕐 Selesai: " . now()->format('d/m/Y H:i') . "\n\n" .
                        "Mohon konfirmasi jika sudah sesuai dengan menutup tiket.";

                    $this->sendMessage($userMessage, [$userPhone]);
                    Log::info('Work Order done notification sent to user', [
                        'work_order_id' => $workOrder->id,
                        'user_id' => $ticket->user->id
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to send Work Order done notification', [
                'work_order_id' => $workOrder->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send SLA Warning
     */
    public function sendSLAWarning(Ticket $ticket, int $hours): void
    {
        try {
            if (!$this->token || !$this->groupId)
                return;

            $message = $this->buildMessage('sla_warning', $ticket, [
                'duration_hours' => (string) $hours,
                'staff_name' => $ticket->assignedStaff->first()->name ?? 'Belum ada',
            ]);

            $this->sendMessageToGroup($message);
            Log::info('SLA Warning sent', ['ticket_id' => $ticket->id]);

        } catch (\Exception $e) {
            Log::error('Failed to send SLA Warning', ['ticket_id' => $ticket->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send Routine Maintenance Reminder
     */
    public function sendRoutineMaintenanceReminder(User $staff, \App\Models\JobTemplate $job): void
    {
        try {
            if (!$this->token || !$staff->phone_number)
                return;

            $template = WhatsAppTemplate::getActiveByType('routine_maintenance_reminder');
            if ($template) {
                $data = [
                    'staff_name' => $staff->name,
                    'job_name' => $job->name,
                    'branch_name' => 'Semua Cabang', // Or specific logic
                ];
                $message = $template->renderContent($data);
                $phone = $this->formatPhoneNumber($staff->phone_number);
                if ($phone) {
                    $this->sendMessage($message, [$phone]);
                    Log::info('Maintenance reminder sent', ['user_id' => $staff->id]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to send maintenance reminder', ['user_id' => $staff->id, 'error' => $e->getMessage()]);
        }
    }


    /**
     * Build message from template with fallback
     */
    private function buildMessage(string $templateType, Ticket $ticket, array $extraData = []): string
    {
        $priorityText = $this->getPriorityText($ticket->priority->value);
        $statusText = $this->getStatusText($ticket->status->value);
        $branchName = $ticket->branch ? $ticket->branch->name : 'Tidak ditentukan';

        // Determine technician name (first assigned staff or 'Tim Support')
        $technician = 'Tim Support';
        if ($ticket->assignedStaff && $ticket->assignedStaff->count() > 0) {
            $technician = $ticket->assignedStaff->first()->name;
        }

        // Try load template from DB by TYPE
        $template = WhatsAppTemplate::getActiveByType($templateType);

        if ($template) {
            $content = $template->content;

            // Common replacements
            $replacements = [
                '{ticket_code}' => $ticket->code,
                '{title}' => $ticket->category ? $ticket->category->name : 'Tiket',
                '{reporter_name}' => $ticket->user->name,
                '{branch_name}' => $branchName,
                '{priority}' => $priorityText,
                '{status}' => $statusText,
                '{description}' => $ticket->description ?? '-',
                '{created_at}' => $ticket->created_at->format('d/m/Y H:i'),
                '{updated_at}' => $ticket->updated_at->format('d/m/Y H:i'),
                '{completed_at}' => $ticket->completed_at ? $ticket->completed_at->format('d/m/Y H:i') : '-',
                '{staff_name}' => $extraData['staff_name'] ?? $technician,
                // Extra data keys
                '{old_status}' => $extraData['old_status'] ?? '',
                '{new_status}' => $extraData['new_status'] ?? $statusText,
                '{replier_name}' => $extraData['replier_name'] ?? '',
                '{reply_content}' => $extraData['reply_content'] ?? '',
                '{duration_hours}' => $extraData['duration_hours'] ?? '0',
            ];

            return str_replace(array_keys($replacements), array_values($replacements), $content);
        }

        // Fallback to hardcoded messages if template not found
        return match ($templateType) {
            'ticket_created' => $this->buildNewTicketMessageFallback($ticket),
            'ticket_status_update' => $this->buildStatusUpdateMessageFallback($ticket, $extraData['old_status'] ?? ''),
            'ticket_reply' => $this->buildReplyMessageFallback($ticket, $extraData['reply_content'] ?? '', $extraData['replier_name'] ?? ''),
            'ticket_assigned' => $this->buildAssignmentMessageFallback($ticket, $extraData['staff_name'] ?? ''),
            'ticket_unassigned_user_alert' => "Tiket {$ticket->code} belum di-assign. Hubungi Admin.",
            'ticket_unassigned_admin_alert' => "Alert: Tiket {$ticket->code} belum di-assign > 1 jam.",
            'ticket_closed' => $this->buildTicketClosedGroupFallback($ticket),
            'ticket_closed_user' => $this->buildTicketClosedUserFallback($ticket),
            'ticket_closed_staff' => $this->buildTicketClosedStaffFallback($ticket),
            default => "Notifikasi Tiket: {$ticket->code} - " . ($ticket->category ? $ticket->category->name : 'Tiket')
        };
    }

    // --- RECIPIENT HELPERS ---

    private function getAdminRecipients(): array
    {
        $recipients = [];
        $admins = User::role(['admin', 'superadmin'])->whereNotNull('phone_number')->get();
        foreach ($admins as $admin) {
            $phone = $this->formatPhoneNumber($admin->phone_number);
            if ($phone)
                $recipients[] = $phone;
        }
        return array_unique($recipients);
    }

    private function getNotificationRecipients(Ticket $ticket): array
    {
        return $this->getAdminRecipients();
    }

    private function getStatusUpdateRecipients(Ticket $ticket): array
    {
        $recipients = [];
        if ($ticket->user && $ticket->user->phone_number) {
            $phone = $this->formatPhoneNumber($ticket->user->phone_number);
            if ($phone)
                $recipients[] = $phone;
        }
        return $recipients;
    }

    private function getAssignmentRecipients(Ticket $ticket, array $oldAssignedStaff): array
    {
        $recipients = [];
        $newAssignedStaff = $ticket->assignedStaff->pluck('id')->toArray();
        $newlyAssigned = array_diff($newAssignedStaff, $oldAssignedStaff);

        if (!empty($newlyAssigned)) {
            $newStaff = User::whereIn('id', $newlyAssigned)->whereNotNull('phone_number')->get();
            foreach ($newStaff as $staff) {
                $phone = $this->formatPhoneNumber($staff->phone_number);
                if ($phone) {
                    $recipients[] = ['phone' => $phone, 'name' => $staff->name];
                }
            }
        }
        return $recipients;
    }

    private function getReplyRecipients(Ticket $ticket, string $replierName): array
    {
        $recipients = [];

        // 1. Ticket Creator (if not replier)
        if ($ticket->user && $ticket->user->phone_number && $ticket->user->name !== $replierName) {
            $phone = $this->formatPhoneNumber($ticket->user->phone_number);
            if ($phone)
                $recipients[] = $phone;
        }

        // 2. Assigned Staff ONLY (if not replier)
        if ($ticket->assignedStaff && $ticket->assignedStaff->count() > 0) {
            foreach ($ticket->assignedStaff as $staff) {
                if ($staff->phone_number && $staff->name !== $replierName) {
                    $phone = $this->formatPhoneNumber($staff->phone_number);
                    if ($phone && !in_array($phone, $recipients)) {
                        $recipients[] = $phone;
                    }
                }
            }
        }

        return array_unique($recipients);
    }

    // --- FALLBACK METHODS & UTILITIES ---

    private function buildNewTicketMessageFallback(Ticket $ticket): string
    {
        $categoryName = $ticket->category ? $ticket->category->name : 'Tiket';
        return "🚨 *TIKET BARU* 🚨\nKode: {$ticket->code}\nKategori: {$categoryName}\nMohon dicek.";
    }

    private function buildStatusUpdateMessageFallback(Ticket $ticket, string $oldStatusText): string
    {
        return "📢 *UPDATE STATUS* 📢\nKode: {$ticket->code}\nStatus: {$ticket->status->value}\nCek aplikasi.";
    }

    private function buildReplyMessageFallback(Ticket $ticket, string $replyContent, string $replierName): string
    {
        return "💬 *BALASAN BARU* 💬\nKode: {$ticket->code}\nDari: {$replierName}\nCek aplikasi.";
    }

    private function buildAssignmentMessageFallback(Ticket $ticket, string $staffName): string
    {
        return "👋 Hi {$staffName}, Anda ditugaskan ke tiket {$ticket->code}.";
    }

    private function buildTicketClosedGroupFallback(Ticket $ticket): string
    {
        $categoryName = $ticket->category ? $ticket->category->name : 'Tiket';
        $branchName = $ticket->branch ? $ticket->branch->name : '-';
        $completedAt = $ticket->completed_at ? $ticket->completed_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');

        return "✅ *TIKET SELESAI* ✅\n\n" .
            "Kode: {$ticket->code}\n" .
            "Kategori: {$categoryName}\n" .
            "Cabang: {$branchName}\n" .
            "Selesai: {$completedAt}\n\n" .
            "Tiket telah ditutup dan diselesaikan.";
    }

    private function buildTicketClosedUserFallback(Ticket $ticket): string
    {
        $categoryName = $ticket->category ? $ticket->category->name : 'Tiket';
        $userName = $ticket->user ? $ticket->user->name : 'Pengguna';

        return "✅ *TIKET ANDA TELAH SELESAI* ✅\n\n" .
            "Halo {$userName},\n\n" .
            "Tiket Anda telah diselesaikan:\n\n" .
            "📌 Kode: {$ticket->code}\n" .
            "📋 Kategori: {$categoryName}\n" .
            "🕐 Selesai: " . now()->format('d/m/Y H:i') . "\n\n" .
            "Terima kasih telah menggunakan layanan kami.\n" .
            "Jika ada pertanyaan, silakan buat tiket baru.";
    }

    private function buildTicketClosedStaffFallback(Ticket $ticket): string
    {
        $categoryName = $ticket->category ? $ticket->category->name : 'Tiket';
        $branchName = $ticket->branch ? $ticket->branch->name : '-';
        $userName = $ticket->user ? $ticket->user->name : 'Pengguna';

        return "✅ *TIKET SELESAI* ✅\n\n" .
            "Tiket yang Anda handle telah ditutup:\n\n" .
            "📌 Kode: {$ticket->code}\n" .
            "📋 Kategori: {$categoryName}\n" .
            "🏢 Cabang: {$branchName}\n" .
            "👤 Pelapor: {$userName}\n" .
            "🕐 Selesai: " . now()->format('d/m/Y H:i') . "\n\n" .
            "Terima kasih atas kerja keras Anda! 🎉";
    }

    private function sendMessageToGroup(string $message): void
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => $this->token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->asForm()
            ->post($this->apiUrl, [
                'target' => $this->groupId,
                'message' => $message,
                'delay' => $this->delay,
            ]);

        if (!$response->successful()) {
            throw new \Exception('WhatsApp API request failed: ' . $response->body());
        }
    }

    private function sendMessage(string $message, array $recipients): void
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => $this->token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->asForm()
            ->post($this->apiUrl, [
                'target' => implode(',', $recipients),
                'message' => $message,
                'delay' => $this->delay,
                'countryCode' => '62',
            ]);

        if (!$response->successful()) {
            throw new \Exception('WhatsApp API request failed: ' . $response->body());
        }
    }

    private function formatPhoneNumber(?string $phone): ?string
    {
        if (!$phone)
            return null;
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }
        if (strlen($phone) < 11 || strlen($phone) > 15)
            return null;
        return $phone;
    }

    private function getPriorityText(string $priority): string
    {
        return match ($priority) {
            'low' => '🟢 Rendah',
            'medium' => '🟡 Sedang',
            'high' => '🟠 Tinggi',
            'urgent' => '🔴 Urgent',
            default => '❓ Tidak diketahui',
        };
    }

    private function getStatusText(string $status): string
    {
        return match ($status) {
            'open' => '🔵 Open',
            'in_progress' => '🟡 In Progress',
            'resolved' => '✅ Resolved',
            'closed' => '🔒 Closed',
            'rejected' => '❌ Rejected',
            default => '❓ Tidak diketahui',
        };
    }

    /**
     * Get the channel name
     */
    public function getChannelName(): string
    {
        return 'whatsapp';
    }

    /**
     * Check if channel is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->token);
    }

    /**
     * Send test message to specific recipient
     */
    public function sendTestMessage(string $recipient, string $message): bool
    {
        try {
            if (!$this->token) {
                return false;
            }

            $phone = $this->formatPhoneNumber($recipient);
            if (!$phone) {
                return false;
            }

            $this->sendMessage($message, [$phone]);
            return true;
        } catch (\Exception $e) {
            Log::error('WhatsApp test message failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send test message to group
     */
    public function sendTestMessageToGroup(string $message): bool
    {
        try {
            if (!$this->token || !$this->groupId) {
                return false;
            }

            $this->sendMessageToGroup($message);
            return true;
        } catch (\Exception $e) {
            Log::error('WhatsApp test group message failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
