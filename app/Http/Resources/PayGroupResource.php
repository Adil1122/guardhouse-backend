<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayGroupResource extends JsonResource
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
            'mode' => $this->mode,
            'name' => $this->name,
            'base_rate' => $this->base_rate,
            'rates' => PayGroupRateResource::collection($this->rates),
            'created_at' => date('Y-m-d H:i A', strtotime($this->created_at)),
        ];
    }
}
