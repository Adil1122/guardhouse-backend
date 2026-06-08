<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManualInvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'service' => $this->service,
            'date' => $this->date ? date('Y-m-d', strtotime($this->date)) : null,
            'total_amount' => $this->total_amount,
            'note' => $this->note,
            'created_at' => date('Y-m-d H:i A', strtotime($this->created_at)),
        ];
    }
}
