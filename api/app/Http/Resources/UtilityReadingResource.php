<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\DailyRecordResource;

class UtilityReadingResource extends JsonResource
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
            'category' => $this->category,
            'sub_type' => $this->sub_type,
            'location' => $this->location,
            'meter_value' => $this->meter_value,
            'photo' => $this->photo ? asset('storage/' . $this->photo) : null,
            // Fields for Gas category
            'stove_type' => $this->stove_type,
            'gas_type' => $this->gas_type,
            // Fields for Electricity category (WBP and LWBP)
            'meter_value_wbp' => $this->meter_value_wbp,
            'meter_value_lwbp' => $this->meter_value_lwbp,
            'photo_wbp' => $this->photo_wbp ? asset('storage/' . $this->photo_wbp) : null,
            'photo_lwbp' => $this->photo_lwbp ? asset('storage/' . $this->photo_lwbp) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'daily_record' => new DailyRecordResource($this->whenLoaded('dailyRecord')),
        ];
    }
}
