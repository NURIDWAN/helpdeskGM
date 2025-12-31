<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\BranchResource;
use App\Http\Resources\ElectricityReadingResource;

class DailyRecordResource extends JsonResource
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
            'total_customers' => $this->total_customers,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'utility_readings' => UtilityReadingResource::collection($this->whenLoaded('utilityReadings')),
            'electricity_readings' => ElectricityReadingResource::collection($this->whenLoaded('electricityReadings')),
            'previous_readings' => $this->previous_readings,
        ];
    }
}


