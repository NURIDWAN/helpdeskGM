<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\WorkReportResource;

class WorkReportAttachmentResource extends JsonResource
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
            'work_report_id' => $this->work_report_id,
            'file_path' => asset('storage/' . $this->file_path),
            'file_type' => $this->file_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'work_report' => new WorkReportResource($this->whenLoaded('workReport')),
        ];
    }
}
