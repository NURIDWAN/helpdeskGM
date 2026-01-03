<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\BranchResource;
use App\Http\Resources\WorkOrderResource;

class TicketResource extends JsonResource
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
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'replies_count' => $this->replies_count ?? 0,
            'notif_staff_sent' => $this->notif_staff_sent,
            'notif_group_sent' => $this->notif_group_sent,
            'user' => new UserResource($this->whenLoaded('user')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'category' => $this->whenLoaded('category'),
            'assigned_staff' => UserResource::collection($this->whenLoaded('assignedStaff')),
            'work_order' => WorkOrderResource::make($this->whenLoaded('workOrder')),
        ];
    }
}
