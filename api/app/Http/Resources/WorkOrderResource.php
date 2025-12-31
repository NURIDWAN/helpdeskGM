<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\TicketResource;
use App\Http\Resources\UserResource;

class WorkOrderResource extends JsonResource
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
            'ticket_id' => $this->ticket_id,
            'assigned_to' => $this->assigned_to,
            'number' => $this->number,
            'description' => $this->description,
            'status' => $this->status,
            'damage_unit' => $this->damage_unit,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->contact_phone,
            'product_type' => $this->product_type,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'purchase_date' => $this->purchase_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'ticket' => new TicketResource($this->whenLoaded('ticket')),
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
        ];
    }
}
