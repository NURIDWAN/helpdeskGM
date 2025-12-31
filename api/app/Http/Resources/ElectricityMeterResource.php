<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElectricityMeterResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'meter_name' => $this->meter_name,
            'meter_number' => $this->meter_number,
            'location' => $this->location,
            'power_capacity' => $this->power_capacity,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'branch' => new BranchResource($this->whenLoaded('branch')),
        ];
    }
}
