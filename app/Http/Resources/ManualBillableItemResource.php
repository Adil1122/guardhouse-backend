<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManualBillableItemResource extends JsonResource
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
            'service' => $this->service,
            'date' => date('d/m/Y', strtotime($this->date)),
            'total_amount' => $this->total_amount,
            'note' => $this->note,
            'created_at' => date('d/m/Y h:i', strtotime($this->created_at))
        ];
    }
}
