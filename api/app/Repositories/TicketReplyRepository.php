<?php

namespace App\Repositories;

use App\Interfaces\TicketReplyRepositoryInterface;
use App\Models\TicketReply;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketReplyRepository implements TicketReplyRepositoryInterface
{
    public function getAllByTicketId(string $ticketId)
    {
        return TicketReply::with(['user', 'ticket'])
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getAllPaginatedByTicketId(string $ticketId, int $rowPerPage)
    {
        return TicketReply::with(['user', 'ticket'])
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'asc')
            ->paginate($rowPerPage);
    }

    public function getById(string $id)
    {
        return TicketReply::with(['user', 'ticket'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $reply = new TicketReply();
            $reply->ticket_id = $data['ticket_id'];
            $reply->user_id = $data['user_id'];
            $reply->content = $data['content'];
            $reply->save();

            $reply = $reply->load(['user', 'ticket']);

            // Send WhatsApp notification for new reply
            try {
                $whatsappService = app(WhatsAppNotificationService::class);
                $whatsappService->sendTicketReplyNotification(
                    $reply->ticket,
                    $reply->content,
                    $reply->user->name
                );
            } catch (\Exception $e) {
                // Log error but don't fail the reply creation
                Log::error('Failed to send WhatsApp notification for ticket reply', [
                    'reply_id' => $reply->id,
                    'ticket_id' => $reply->ticket_id,
                    'error' => $e->getMessage()
                ]);
            }

            return $reply;
        });
    }

    public function update(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $reply = $this->getById($id);

            $reply->fill([
                'content' => $data['content'] ?? $reply->content,
            ])->save();

            return $reply->load(['user', 'ticket']);
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $reply = $this->getById($id);
            $reply->delete();
            return $reply;
        });
    }
}
