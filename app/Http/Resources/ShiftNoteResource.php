<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftNoteResource extends JsonResource
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
            'shift_id' => $this->shift_id,
            'type' => $this->type,
            'type_details' => $this->type_details,
            'notes' => $this->notes,
            'created_at' => $this->created_at ? date('Y-m-d H:i A', strtotime($this->created_at)) : null,
        ];
    }
}
