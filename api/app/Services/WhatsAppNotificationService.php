<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    private string $apiUrl;
    private string $token;
    private int $delay;
    private string $groupId; // WhatsApp Group ID

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', 'https://api.fonnte.com/send');
        $this->token = config('services.whatsapp.token');
        $this->delay = config('services.whatsapp.delay', 2);
        $this->groupId = '120363322658703628@g.us'; // WhatsApp Group ID
    }

    /**
     * Send notification for new ticket creation
     */
    public function sendNewTicketNotification(Ticket $ticket): void
    {
        try {
            $message = $this->buildNewTicketMessage($ticket);
            $recipients = $this->getNotificationRecipients($ticket);

            if (empty($recipients)) {
                Log::info('No recipients found for ticket notification', ['ticket_id' => $ticket->id]);
                return;
            }

            // Send to group instead of individual recipients
            $this->sendMessageToGroup($message);
            // OLD: Send to individual staff (commented)
            // $this->sendMessage($message, $recipients);

            Log::info('WhatsApp notification sent for new ticket', [
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->code,
                'group_id' => $this->groupId
                // 'recipients_count' => count($recipients) // OLD
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification for new ticket', [
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
            $message = $this->buildStatusUpdateMessage($ticket, $oldStatus);
            $recipients = $this->getStatusUpdateRecipients($ticket);

            if (empty($recipients)) {
                Log::info('No recipients found for status update notification', ['ticket_id' => $ticket->id]);
                return;
            }

            // Send to individual recipients (normal)
            $this->sendMessage($message, $recipients);

            Log::info('WhatsApp notification sent for ticket status update', [
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->code,
                'old_status' => $oldStatus,
                'new_status' => $ticket->status,
                'recipients_count' => count($recipients)
            ]);
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
            $message = $this->buildReplyMessage($ticket, $replyContent, $replierName);
            $recipients = $this->getReplyRecipients($ticket, $replierName);

            if (empty($recipients)) {
                Log::info('No recipients found for reply notification', ['ticket_id' => $ticket->id]);
                return;
            }

            // Send to individual recipients (normal)
            $this->sendMessage($message, $recipients);

            Log::info('WhatsApp notification sent for ticket reply', [
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->code,
                'replier' => $replierName,
                'recipients_count' => count($recipients)
            ]);
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
            $recipients = $this->getAssignmentRecipients($ticket, $oldAssignedStaff);

            if (empty($recipients)) {
                Log::info('No new staff assigned for notification', ['ticket_id' => $ticket->id]);
                return;
            }

            // Send individual messages to each newly assigned staff (normal)
            foreach ($recipients as $recipient) {
                $message = $this->buildAssignmentMessage($ticket, $recipient['name']);
                $this->sendMessage($message, [$recipient['phone']]);
            }

            Log::info('WhatsApp notification sent for ticket assignment', [
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->code,
                'old_assigned_staff' => $oldAssignedStaff,
                'new_assigned_staff' => $ticket->assignedStaff->pluck('id')->toArray(),
                'notified_staff' => array_column($recipients, 'name'),
                'recipients_count' => count($recipients)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification for ticket assignment', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Build message for new ticket notification
     */
    private function buildNewTicketMessage(Ticket $ticket): string
    {
        $priorityText = $this->getPriorityText($ticket->priority->value);
        $statusText = $this->getStatusText($ticket->status->value);
        $branchName = $ticket->branch ? $ticket->branch->name : 'Tidak ditentukan';

        $message = "🚨 *TIKET BARU DITERIMA* 🚨\n\n";
        $message .= "📋 *Kode Tiket:* {$ticket->code}\n";
        $message .= "📝 *Judul:* {$ticket->title}\n";
        $message .= "👤 *Pelapor:* {$ticket->user->name}\n";
        $message .= "🏢 *Cabang:* {$branchName}\n";
        $message .= "⚡ *Prioritas:* {$priorityText}\n";
        $message .= "📊 *Status:* {$statusText}\n";
        $message .= "📅 *Dibuat:* " . $ticket->created_at->format('d/m/Y H:i') . "\n\n";
        $message .= "📄 *Deskripsi:*\n{$ticket->description}\n\n";
        $message .= "🔗 Silakan login ke sistem untuk melihat detail lengkap dan menindaklanjuti tiket ini.\n\n";
        $message .= "_Pesan ini dikirim otomatis oleh sistem GA Maintenance_";

        return $message;
    }

    /**
     * Build message for status update notification
     */
    private function buildStatusUpdateMessage(Ticket $ticket, string $oldStatus): string
    {
        $oldStatusText = $this->getStatusText($oldStatus);
        $newStatusText = $this->getStatusText($ticket->status->value);
        $branchName = $ticket->branch ? $ticket->branch->name : 'Tidak ditentukan';

        $message = "📢 *UPDATE STATUS TIKET* 📢\n\n";
        $message .= "📋 *Kode Tiket:* {$ticket->code}\n";
        $message .= "📝 *Judul:* {$ticket->title}\n";
        $message .= "👤 *Pelapor:* {$ticket->user->name}\n";
        $message .= "🏢 *Cabang:* {$branchName}\n";
        $message .= "📊 *Status Lama:* {$oldStatusText}\n";
        $message .= "📊 *Status Baru:* {$newStatusText}\n";
        $message .= "📅 *Diupdate:* " . $ticket->updated_at->format('d/m/Y H:i') . "\n\n";

        if ($ticket->status->value === 'resolved') {
            $message .= "✅ *Tiket telah diselesaikan!*\n";
            if ($ticket->completed_at) {
                $message .= "⏰ *Waktu Penyelesaian:* " . $ticket->completed_at->format('d/m/Y H:i') . "\n";
            }
        }

        $message .= "\n🔗 Silakan login ke sistem untuk melihat detail lengkap.\n\n";
        $message .= "_Pesan ini dikirim otomatis oleh sistem GA Maintenance_";

        return $message;
    }

    /**
     * Build message for ticket reply notification
     */
    private function buildReplyMessage(Ticket $ticket, string $replyContent, string $replierName): string
    {
        $priorityText = $this->getPriorityText($ticket->priority->value);
        $statusText = $this->getStatusText($ticket->status->value);
        $branchName = $ticket->branch ? $ticket->branch->name : 'Tidak ditentukan';

        $message = "💬 *BALASAN TIKET BARU* 💬\n\n";
        $message .= "📋 *Kode Tiket:* {$ticket->code}\n";
        $message .= "📝 *Judul:* {$ticket->title}\n";
        $message .= "👤 *Pelapor:* {$ticket->user->name}\n";
        $message .= "🏢 *Cabang:* {$branchName}\n";
        $message .= "⚡ *Prioritas:* {$priorityText}\n";
        $message .= "📊 *Status:* {$statusText}\n";
        $message .= "💬 *Balasan dari:* {$replierName}\n";
        $message .= "📅 *Waktu:* " . now()->format('d/m/Y H:i') . "\n\n";
        $message .= "📄 *Isi Balasan:*\n{$replyContent}\n\n";
        $message .= "🔗 Silakan login ke sistem untuk melihat detail lengkap dan memberikan tanggapan.\n\n";
        $message .= "_Pesan ini dikirim otomatis oleh sistem GA Maintenance_";

        return $message;
    }


    /**
     * Get recipients for new ticket notification
     */
    private function getNotificationRecipients(Ticket $ticket): array
    {
        $recipients = [];

        // Get all admins
        $admins = User::role('admin')
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->get();

        foreach ($admins as $admin) {
            $phone = $this->formatPhoneNumber($admin->phone_number);
            if ($phone) {
                $recipients[] = $phone;
            }
        }

        // Get assigned staff only (if any)
        if ($ticket->assignedStaff && $ticket->assignedStaff->count() > 0) {
            foreach ($ticket->assignedStaff as $staff) {
                if ($staff->phone_number) {
                    $phone = $this->formatPhoneNumber($staff->phone_number);
                    if ($phone && !in_array($phone, $recipients)) {
                        $recipients[] = $phone;
                    }
                }
            }
        } else {
            // If no staff assigned, get all staff from the same branch
            if ($ticket->branch_id) {
                $staff = User::role('staff')
                    ->where('branch_id', $ticket->branch_id)
                    ->whereNotNull('phone_number')
                    ->where('phone_number', '!=', '')
                    ->get();

                foreach ($staff as $staffMember) {
                    $phone = $this->formatPhoneNumber($staffMember->phone_number);
                    if ($phone && !in_array($phone, $recipients)) {
                        $recipients[] = $phone;
                    }
                }
            }
        }

        return array_unique($recipients);
    }

    /**
     * Get recipients for status update notification
     */
    private function getStatusUpdateRecipients(Ticket $ticket): array
    {
        $recipients = [];

        // Always notify the ticket creator
        if ($ticket->user && $ticket->user->phone_number) {
            $phone = $this->formatPhoneNumber($ticket->user->phone_number);
            if ($phone) {
                $recipients[] = $phone;
            }
        }

        // Notify assigned staff (if any)
        if ($ticket->assignedStaff && $ticket->assignedStaff->count() > 0) {
            foreach ($ticket->assignedStaff as $staff) {
                if ($staff->phone_number) {
                    $phone = $this->formatPhoneNumber($staff->phone_number);
                    if ($phone && !in_array($phone, $recipients)) {
                        $recipients[] = $phone;
                    }
                }
            }
        }

        // Always notify admins for status updates
        $admins = User::role('admin')
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->get();

        foreach ($admins as $admin) {
            $phone = $this->formatPhoneNumber($admin->phone_number);
            if ($phone && !in_array($phone, $recipients)) {
                $recipients[] = $phone;
            }
        }

        return array_unique($recipients);
    }

    /**
     * Build message for ticket assignment notification (for group) - OLD, not used anymore
     */
    // private function buildAssignmentMessageForGroup(Ticket $ticket, array $recipients): string
    // {
    //     $priorityText = $this->getPriorityText($ticket->priority->value);
    //     $statusText = $this->getStatusText($ticket->status->value);
    //     $branchName = $ticket->branch ? $ticket->branch->name : 'Tidak ditentukan';
    //     $staffNames = implode(', ', array_column($recipients, 'name'));
    //
    //     $message = "👋 *PENUGASAN TIKET BARU* 👋\n\n";
    //     $message .= "📋 *Kode Tiket:* {$ticket->code}\n";
    //     $message .= "📝 *Judul:* {$ticket->title}\n";
    //     $message .= "👤 *Pelapor:* {$ticket->user->name}\n";
    //     $message .= "🏢 *Cabang:* {$branchName}\n";
    //     $message .= "⚡ *Prioritas:* {$priorityText}\n";
    //     $message .= "📊 *Status:* {$statusText}\n";
    //     $message .= "👥 *Ditugaskan ke:* {$staffNames}\n";
    //     $message .= "📅 *Ditugaskan:* " . now()->format('d/m/Y H:i') . "\n\n";
    //     $message .= "📄 *Deskripsi:*\n{$ticket->description}\n\n";
    //     $message .= "🔗 Silakan login ke sistem untuk melihat detail lengkap dan menindaklanjuti tiket ini.\n\n";
    //     $message .= "_Pesan ini dikirim otomatis oleh sistem GA Maintenance_";
    //
    //     return $message;
    // }

    /**
     * Build message for ticket assignment notification (for individual staff)
     */
    private function buildAssignmentMessage(Ticket $ticket, string $staffName): string
    {
        $priorityText = $this->getPriorityText($ticket->priority->value);
        $statusText = $this->getStatusText($ticket->status->value);
        $branchName = $ticket->branch ? $ticket->branch->name : 'Tidak ditentukan';

        $message = "👋 Hi {$staffName}, kamu telah ditugaskan untuk menangani tiket berikut:\n\n";
        $message .= "📋 *Kode Tiket:* {$ticket->code}\n";
        $message .= "📝 *Judul:* {$ticket->title}\n";
        $message .= "👤 *Pelapor:* {$ticket->user->name}\n";
        $message .= "🏢 *Cabang:* {$branchName}\n";
        $message .= "⚡ *Prioritas:* {$priorityText}\n";
        $message .= "📊 *Status:* {$statusText}\n";
        $message .= "📅 *Ditugaskan:* " . now()->format('d/m/Y H:i') . "\n\n";
        $message .= "📄 *Deskripsi:*\n{$ticket->description}\n\n";
        $message .= "🔗 Silakan login ke sistem untuk melihat detail lengkap dan menindaklanjuti tiket ini.\n\n";
        $message .= "_Pesan ini dikirim otomatis oleh sistem GA Maintenance_";

        return $message;
    }

    /**
     * Get recipients for ticket assignment notification
     */
    private function getAssignmentRecipients(Ticket $ticket, array $oldAssignedStaff): array
    {
        $recipients = [];

        // Get current assigned staff IDs
        $newAssignedStaff = $ticket->assignedStaff->pluck('id')->toArray();

        // Only notify newly assigned staff (who weren't assigned before)
        $newlyAssigned = array_diff($newAssignedStaff, $oldAssignedStaff);
        if (!empty($newlyAssigned)) {
            $newStaff = User::whereIn('id', $newlyAssigned)
                ->whereNotNull('phone_number')
                ->where('phone_number', '!=', '')
                ->get();

            foreach ($newStaff as $staff) {
                $phone = $this->formatPhoneNumber($staff->phone_number);
                if ($phone) {
                    $recipients[] = [
                        'phone' => $phone,
                        'name' => $staff->name
                    ];
                }
            }
        }

        return $recipients;
    }

    /**
     * Get recipients for ticket reply notification
     */
    private function getReplyRecipients(Ticket $ticket, string $replierName): array
    {
        $recipients = [];

        // Always notify the ticket creator (if not the replier)
        if ($ticket->user && $ticket->user->phone_number && $ticket->user->name !== $replierName) {
            $phone = $this->formatPhoneNumber($ticket->user->phone_number);
            if ($phone) {
                $recipients[] = $phone;
            }
        }

        // Notify assigned staff (if any and not the replier)
        if ($ticket->assignedStaff && $ticket->assignedStaff->count() > 0) {
            foreach ($ticket->assignedStaff as $staff) {
                if ($staff->phone_number && $staff->name !== $replierName) {
                    $phone = $this->formatPhoneNumber($staff->phone_number);
                    if ($phone && !in_array($phone, $recipients)) {
                        $recipients[] = $phone;
                    }
                }
            }
        } else {
            // If no staff assigned, get all staff from the same branch (excluding replier)
            if ($ticket->branch_id) {
                $staff = User::role('staff')
                    ->where('branch_id', $ticket->branch_id)
                    ->whereNotNull('phone_number')
                    ->where('phone_number', '!=', '')
                    ->get();

                foreach ($staff as $staffMember) {
                    if ($staffMember->name !== $replierName) {
                        $phone = $this->formatPhoneNumber($staffMember->phone_number);
                        if ($phone && !in_array($phone, $recipients)) {
                            $recipients[] = $phone;
                        }
                    }
                }
            }
        }

        // Always notify admins (excluding replier)
        $admins = User::role('admin')
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->get();

        foreach ($admins as $admin) {
            if ($admin->name !== $replierName) {
                $phone = $this->formatPhoneNumber($admin->phone_number);
                if ($phone && !in_array($phone, $recipients)) {
                    $recipients[] = $phone;
                }
            }
        }

        return array_unique($recipients);
    }



    /**
     * Send message to WhatsApp group
     */
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

        $responseData = $response->json();

        if (isset($responseData['status'])) {
            if ($responseData['status'] === 'success' || $responseData['status'] === true) {
                Log::info('WhatsApp message sent to group successfully', [
                    'group_id' => $this->groupId,
                    'response' => $responseData
                ]);
                return;
            } else {
                throw new \Exception('WhatsApp API error: ' . ($responseData['message'] ?? 'Unknown error'));
            }
        }
    }

    /**
     * Send message via WhatsApp API (send to individual staff)
     */
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

        $responseData = $response->json();

        if (isset($responseData['status'])) {
            if ($responseData['status'] === 'success' || $responseData['status'] === true) {
                Log::info('WhatsApp message sent successfully', [
                    'recipients' => $recipients,
                    'response' => $responseData
                ]);
                return;
            } else {
                throw new \Exception('WhatsApp API error: ' . ($responseData['message'] ?? 'Unknown error'));
            }
        }
    }

    /**
     * Format phone number for WhatsApp API
     */
    private function formatPhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading 0 and add 62 if needed
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        // Validate phone number length (Indonesian mobile numbers)
        if (strlen($phone) < 11 || strlen($phone) > 15) {
            return null;
        }

        return $phone;
    }

    /**
     * Get priority text in Indonesian
     */
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

    /**
     * Get status text in Indonesian
     */
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
