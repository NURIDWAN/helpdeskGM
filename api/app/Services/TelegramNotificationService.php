<?php

namespace App\Services;

use App\Contracts\NotificationChannelInterface;
use App\Models\JobTemplate;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppTemplate;
use App\Models\WorkOrder;
use App\Models\WorkReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService implements NotificationChannelInterface
{
    private ?string $botToken;
    private ?string $chatId; // Group/Channel ID
    private string $apiUrl;
    private string $appUrl;

    public function __construct()
    {
        // Load settings from database (reusing whatsapp_settings table)
        $this->botToken = WhatsAppSetting::getValue('telegram_bot_token')
            ?: config('services.telegram.bot_token');

        $this->chatId = WhatsAppSetting::getValue('telegram_chat_id')
            ?: config('services.telegram.chat_id');

        $this->apiUrl = 'https://api.telegram.org/bot';
        $this->appUrl = config('app.frontend_url', config('app.url'));
    }

    /**
     * Get the channel name
     */
    public function getChannelName(): string
    {
        return 'telegram';
    }

    /**
     * Check if channel is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->botToken);
    }

    /**
     * Send notification for new ticket creation
     * @return array ['group' => bool|null, 'staff' => bool|null]
     */
    public function sendNewTicketNotification(Ticket $ticket): array
    {
        $result = ['group' => null, 'staff' => null];

        try {
            if (!$this->botToken) {
                return $result;
            }

            // Build message
            $message = $this->buildMessage('ticket_created', $ticket);

            // Send to Group
            if ($this->chatId) {
                try {
                    $this->sendMessageToGroup($message);
                    $result['group'] = true;
                    Log::info('Telegram notification sent to group for new ticket', [
                        'ticket_id' => $ticket->id,
                        'chat_id' => $this->chatId
                    ]);
                } catch (\Exception $e) {
                    $result['group'] = false;
                    Log::error('Failed to send Telegram to group', [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Send to Assigned Staff
            if ($ticket->assignedStaff && $ticket->assignedStaff->count() > 0) {
                try {
                    $staffMessage = $this->buildMessage('ticket_assigned', $ticket);
                    $sentCount = 0;

                    foreach ($ticket->assignedStaff as $staff) {
                        if ($staff->telegram_chat_id) {
                            $this->sendMessage($staffMessage, $staff->telegram_chat_id);
                            $sentCount++;
                        }
                    }

                    if ($sentCount > 0) {
                        $result['staff'] = true;
                        Log::info('Telegram notification sent to staff for new ticket', [
                            'ticket_id' => $ticket->id,
                            'staff_count' => $sentCount
                        ]);
                    }
                } catch (\Exception $e) {
                    $result['staff'] = false;
                    Log::error('Failed to send Telegram to staff', [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification for new ticket', [
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
            if (!$this->botToken || !$ticket->user || !$ticket->user->telegram_chat_id) {
                return;
            }

            $message = $this->buildMessage('ticket_created_user', $ticket);
            $this->sendMessage($message, $ticket->user->telegram_chat_id);

            Log::info('Telegram confirmation sent to user', ['ticket_id' => $ticket->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram confirmation to user', [
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
            if (!$this->botToken) {
                return;
            }

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

            if ($status === 'closed') {
                // Send to Group
                if ($this->chatId) {
                    $this->sendMessageToGroup($message);
                }

                // Send to User
                if ($ticket->user && $ticket->user->telegram_chat_id) {
                    $userMessage = $this->buildMessage('ticket_closed_user', $ticket, [
                        'old_status' => $this->getStatusText($oldStatus),
                        'new_status' => $this->getStatusText($status)
                    ]);
                    $this->sendMessage($userMessage, $ticket->user->telegram_chat_id);
                }

                // Send to Assigned Staff
                if ($ticket->assignedStaff && $ticket->assignedStaff->count() > 0) {
                    $staffMessage = $this->buildMessage('ticket_closed_staff', $ticket, [
                        'old_status' => $this->getStatusText($oldStatus),
                        'new_status' => $this->getStatusText($status)
                    ]);

                    foreach ($ticket->assignedStaff as $staff) {
                        if ($staff->telegram_chat_id) {
                            $this->sendMessage($staffMessage, $staff->telegram_chat_id);
                        }
                    }
                }
            } else {
                // Progress/Resolved -> Send to User
                if ($ticket->user && $ticket->user->telegram_chat_id) {
                    $this->sendMessage($message, $ticket->user->telegram_chat_id);
                }
            }

            Log::info('Telegram notification sent for status update', [
                'ticket_id' => $ticket->id,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification for status update', [
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
            if (!$this->botToken) {
                return;
            }

            $message = $this->buildMessage('ticket_reply', $ticket, [
                'replier_name' => $replierName,
                'reply_content' => $replyContent
            ]);

            // Send to Creator (if not replier)
            if ($ticket->user && $ticket->user->telegram_chat_id && $ticket->user->name !== $replierName) {
                $this->sendMessage($message, $ticket->user->telegram_chat_id);
            }

            // Send to Assigned Staff (if not replier)
            if ($ticket->assignedStaff && $ticket->assignedStaff->count() > 0) {
                foreach ($ticket->assignedStaff as $staff) {
                    if ($staff->telegram_chat_id && $staff->name !== $replierName) {
                        $this->sendMessage($message, $staff->telegram_chat_id);
                    }
                }
            }

            Log::info('Telegram notification sent for ticket reply', ['ticket_id' => $ticket->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification for ticket reply', [
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
            if (!$this->botToken) {
                return;
            }

            $newAssignedStaff = $ticket->assignedStaff->pluck('id')->toArray();
            $newlyAssigned = array_diff($newAssignedStaff, $oldAssignedStaff);

            if (empty($newlyAssigned)) {
                return;
            }

            $newStaff = User::whereIn('id', $newlyAssigned)
                ->whereNotNull('telegram_chat_id')
                ->get();

            foreach ($newStaff as $staff) {
                $message = $this->buildMessage('ticket_assigned', $ticket, [
                    'staff_name' => $staff->name
                ]);
                $this->sendMessage($message, $staff->telegram_chat_id);
            }

            Log::info('Telegram notification sent for ticket assignment', [
                'ticket_id' => $ticket->id,
                'count' => count($newlyAssigned)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification for ticket assignment', [
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
            if (!$this->botToken) {
                return;
            }

            // Alert to User
            if ($ticket->user && $ticket->user->telegram_chat_id) {
                $userMessage = $this->buildMessage('ticket_unassigned_user_alert', $ticket);
                $this->sendMessage($userMessage, $ticket->user->telegram_chat_id);
            }

            // Alert to Group
            if ($this->chatId) {
                $adminMessage = $this->buildMessage('ticket_unassigned_admin_alert', $ticket);
                $this->sendMessageToGroup($adminMessage);
            }

            // Alert to Admins
            $admins = User::role(['admin', 'superadmin'])
                ->whereNotNull('telegram_chat_id')
                ->get();

            foreach ($admins as $admin) {
                $adminMessage = $this->buildMessage('ticket_unassigned_admin_alert', $ticket);
                $this->sendMessage($adminMessage, $admin->telegram_chat_id);
            }

            Log::info('Telegram unassigned ticket alert sent', ['ticket_id' => $ticket->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram unassigned ticket alert', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification for new work order (SPK) creation
     */
    public function sendWorkOrderNotification(WorkOrder $workOrder): ?bool
    {
        try {
            if (!$this->botToken) {
                return null;
            }

            $technician = $workOrder->assignedUser;
            if (!$technician || !$technician->telegram_chat_id) {
                return null;
            }

            $ticket = $workOrder->ticket;
            $ticketInfo = $ticket ? "Tiket: {$ticket->code}" : "SPK Standalone";
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

            $this->sendMessage($message, $technician->telegram_chat_id);

            Log::info('Telegram work order notification sent', [
                'work_order_id' => $workOrder->id,
                'technician_id' => $technician->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram work order notification', [
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
            if (!$this->botToken || !$this->chatId) {
                return;
            }

            $workOrder = $report->workOrder;

            $data = [
                'ticket_code' => $workOrder ? $workOrder->number : ($report->custom_job ?? '-'),
                'staff_name' => $report->user->name,
                'branch_name' => $report->branch ? $report->branch->name : '-',
                'status' => $report->status->value,
                'description' => $report->description ?? '-',
            ];

            $template = WhatsAppTemplate::getActiveByType('work_report_created');
            if ($template) {
                $message = $template->renderContent($data);
                $this->sendMessageToGroup($message);
                Log::info('Telegram work report notification sent to group', ['report_id' => $report->id]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram work report notification', [
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
            if (!$this->botToken) {
                return;
            }

            $ticket = $workOrder->ticket;
            if (!$ticket || !$ticket->user || !$ticket->user->telegram_chat_id) {
                return;
            }

            $message = $this->buildMessage('work_order_completed_user', $ticket, [
                'staff_name' => $workOrder->assignedUser ? $workOrder->assignedUser->name : 'Teknisi',
            ]);

            $this->sendMessage($message, $ticket->user->telegram_chat_id);
            Log::info('Telegram work order completion sent to user', ['work_order_id' => $workOrder->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram SPK complete notification', [
                'work_order_id' => $workOrder->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification when Work Order is marked as "done"
     */
    public function sendWorkOrderDoneNotification(WorkOrder $workOrder): void
    {
        try {
            if (!$this->botToken) {
                return;
            }

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
            if ($this->chatId) {
                $this->sendMessageToGroup($message);
            }

            // 2. Send to Assigned Staff
            if ($workOrder->assignedStaff && $workOrder->assignedStaff->count() > 0) {
                $staffMessage = "✅ *SPK ANDA TELAH SELESAI* ✅\n\n" .
                    "No. SPK: {$workOrder->number}\n" .
                    "📌 {$ticketInfo}\n" .
                    "📋 Kategori: {$categoryName}\n" .
                    "🏢 Cabang: {$branchName}\n" .
                    "🕐 Selesai: " . now()->format('d/m/Y H:i') . "\n\n" .
                    "Terima kasih atas kerja keras Anda! 🎉";

                foreach ($workOrder->assignedStaff as $staff) {
                    if ($staff->telegram_chat_id) {
                        $this->sendMessage($staffMessage, $staff->telegram_chat_id);
                    }
                }
            } elseif ($workOrder->assignedUser && $workOrder->assignedUser->telegram_chat_id) {
                $staffMessage = "✅ *SPK ANDA TELAH SELESAI* ✅\n\n" .
                    "No. SPK: {$workOrder->number}\n" .
                    "📌 {$ticketInfo}\n" .
                    "📋 Kategori: {$categoryName}\n" .
                    "🏢 Cabang: {$branchName}\n" .
                    "🕐 Selesai: " . now()->format('d/m/Y H:i') . "\n\n" .
                    "Terima kasih atas kerja keras Anda! 🎉";

                $this->sendMessage($staffMessage, $workOrder->assignedUser->telegram_chat_id);
            }

            // 3. Send to User (Ticket Creator)
            if ($ticket && $ticket->user && $ticket->user->telegram_chat_id) {
                $userMessage = "📋 *SPK SELESAI* 📋\n\n" .
                    "Halo {$ticket->user->name},\n\n" .
                    "Pekerjaan untuk tiket Anda telah selesai:\n\n" .
                    "📌 Kode Tiket: {$ticket->code}\n" .
                    "📋 Kategori: {$categoryName}\n" .
                    "👷 Teknisi: {$staffNamesStr}\n" .
                    "🕐 Selesai: " . now()->format('d/m/Y H:i') . "\n\n" .
                    "Mohon konfirmasi jika sudah sesuai dengan menutup tiket.";

                $this->sendMessage($userMessage, $ticket->user->telegram_chat_id);
            }

            Log::info('Telegram work order done notification sent', ['work_order_id' => $workOrder->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram work order done notification', [
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
            if (!$this->botToken || !$this->chatId) {
                return;
            }

            $message = $this->buildMessage('sla_warning', $ticket, [
                'duration_hours' => (string) $hours,
                'staff_name' => $ticket->assignedStaff->first()->name ?? 'Belum ada',
            ]);

            $this->sendMessageToGroup($message);
            Log::info('Telegram SLA Warning sent', ['ticket_id' => $ticket->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram SLA Warning', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send Routine Maintenance Reminder
     */
    public function sendRoutineMaintenanceReminder(User $staff, JobTemplate $job): void
    {
        try {
            if (!$this->botToken || !$staff->telegram_chat_id) {
                return;
            }

            $template = WhatsAppTemplate::getActiveByType('routine_maintenance_reminder');
            if ($template) {
                $data = [
                    'staff_name' => $staff->name,
                    'job_name' => $job->name,
                    'branch_name' => 'Semua Cabang',
                ];
                $message = $template->renderContent($data);
                $this->sendMessage($message, $staff->telegram_chat_id);
                Log::info('Telegram maintenance reminder sent', ['user_id' => $staff->id]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram maintenance reminder', [
                'user_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send test message to specific recipient
     */
    public function sendTestMessage(string $recipient, string $message): bool
    {
        try {
            if (!$this->botToken) {
                return false;
            }

            $this->sendMessage($message, $recipient);
            return true;
        } catch (\Exception $e) {
            Log::error('Telegram test message failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send test message to group
     */
    public function sendTestMessageToGroup(string $message): bool
    {
        try {
            if (!$this->botToken || !$this->chatId) {
                return false;
            }

            $this->sendMessageToGroup($message);
            return true;
        } catch (\Exception $e) {
            Log::error('Telegram test group message failed', ['error' => $e->getMessage()]);
            return false;
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

        $technician = 'Tim Support';
        if ($ticket->assignedStaff && $ticket->assignedStaff->count() > 0) {
            $technician = $ticket->assignedStaff->first()->name;
        }

        // Try load template from DB
        $template = WhatsAppTemplate::getActiveByType($templateType);

        if ($template) {
            $content = $template->content;

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
                '{old_status}' => $extraData['old_status'] ?? '',
                '{new_status}' => $extraData['new_status'] ?? $statusText,
                '{replier_name}' => $extraData['replier_name'] ?? '',
                '{reply_content}' => $extraData['reply_content'] ?? '',
                '{duration_hours}' => $extraData['duration_hours'] ?? '0',
            ];

            return str_replace(array_keys($replacements), array_values($replacements), $content);
        }

        // Fallback messages
        return match ($templateType) {
            'ticket_created' => $this->buildNewTicketMessageFallback($ticket),
            'ticket_status_update' => $this->buildStatusUpdateMessageFallback($ticket),
            'ticket_reply' => $this->buildReplyMessageFallback($ticket, $extraData['reply_content'] ?? '', $extraData['replier_name'] ?? ''),
            'ticket_assigned' => $this->buildAssignmentMessageFallback($ticket, $extraData['staff_name'] ?? ''),
            'ticket_unassigned_user_alert' => "Tiket {$ticket->code} belum di-assign. Hubungi Admin.",
            'ticket_unassigned_admin_alert' => "Alert: Tiket {$ticket->code} belum di-assign > 1 jam.",
            'ticket_closed' => $this->buildTicketClosedGroupFallback($ticket),
            'ticket_closed_user' => $this->buildTicketClosedUserFallback($ticket),
            'ticket_closed_staff' => $this->buildTicketClosedStaffFallback($ticket),
            default => "Notifikasi Tiket: {$ticket->code}"
        };
    }

    // --- FALLBACK METHODS ---

    private function buildNewTicketMessageFallback(Ticket $ticket): string
    {
        $categoryName = $ticket->category ? $ticket->category->name : 'Tiket';
        return "🚨 *TIKET BARU* 🚨\nKode: {$ticket->code}\nKategori: {$categoryName}\nMohon dicek.";
    }

    private function buildStatusUpdateMessageFallback(Ticket $ticket): string
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
            "Terima kasih telah menggunakan layanan kami.";
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

    // --- SENDING METHODS ---

    private function sendMessageToGroup(string $message): void
    {
        $this->sendMessage($message, $this->chatId);
    }

    private function sendMessage(string $message, string $chatId): void
    {
        $url = $this->apiUrl . $this->botToken . '/sendMessage';

        $response = Http::timeout(30)->post($url, [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ]);

        if (!$response->successful()) {
            $body = $response->json();
            throw new \Exception('Telegram API request failed: ' . ($body['description'] ?? $response->body()));
        }
    }

    // --- UTILITIES ---

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
}
