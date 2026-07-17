<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormPermintaanItemResource extends JsonResource
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
            'product_description' => $this->product_description,
            'quantity' => $this->quantity,
            'uom' => $this->uom,
            'notes' => $this->notes,
            'attachments' => FormPermintaanAttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
