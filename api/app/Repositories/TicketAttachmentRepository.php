<?php

namespace App\Repositories;

use App\Interfaces\TicketAttachmentRepositoryInterface;
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\DB;

class TicketAttachmentRepository implements TicketAttachmentRepositoryInterface
{
    public function getAllByTicketId(string $ticketId)
    {
        return TicketAttachment::where('ticket_id', $ticketId)->get();
    }

    public function getById(string $id)
    {
        return TicketAttachment::with('ticket')->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $attachment = new TicketAttachment();
            $attachment->ticket_id = $data['ticket_id'];
            $attachment->file_path = $data['file_path'];
            $attachment->file_type = $data['file_type'] ?? null;
            $attachment->save();
            return $attachment->load('ticket');
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $attachment = $this->getById($id);
            $attachment->delete();
            return $attachment;
        });
    }
}
