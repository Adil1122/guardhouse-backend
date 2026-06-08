<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceGroupRateResource extends JsonResource
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
            'days' => $this->days,
            'rate' => $this->rate,
            'from_time' => $this->from_time,
            'to_time' => $this->to_time,
            'created_at' => date('Y-m-d H:i A', strtotime($this->created_at)),
        ];
    }
}
