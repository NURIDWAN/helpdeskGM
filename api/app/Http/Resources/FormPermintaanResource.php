<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\BranchResource;
use App\Http\Resources\FormPermintaanItemResource;
use App\Http\Resources\FormPermintaanAttachmentResource;
use App\Http\Resources\TicketResource;

class FormPermintaanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'ticket_id' => $this->ticket_id,
            'date' => $this->date,
            'priority' => $this->priority,
            'request_type' => $this->request_type,
            'fa_number' => $this->fa_number,
            'reason' => $this->reason,
            'status' => $this->status,
            'confirmed_at' => $this->confirmed_at,
            'created_at' => $this->created_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'ticket' => new TicketResource($this->whenLoaded('ticket')),
            'confirmed_by' => new UserResource($this->whenLoaded('confirmedBy')),
            'items' => FormPermintaanItemResource::collection($this->whenLoaded('items')),
            'attachments' => FormPermintaanAttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
