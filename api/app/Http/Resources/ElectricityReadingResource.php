<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElectricityReadingResource extends JsonResource
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
            'daily_record_id' => $this->daily_record_id,
            'electricity_meter_id' => $this->electricity_meter_id,
            'meter_value' => $this->meter_value,
            'photo' => $this->photo ? asset('storage/' . $this->photo) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'daily_record' => new DailyRecordResource($this->whenLoaded('dailyRecord')),
            'electricity_meter' => new ElectricityMeterResource($this->whenLoaded('electricityMeter')),
        ];
    }
}

