<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationComplianceResource extends JsonResource
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
            'name' => $this->name,
            'remind_in_days' => $this->remind_in_days,
            'is_critical' => $this->is_critical,
            'show_to_customer' => $this->show_to_customer,
            'created_at' => date('Y-m-d H:i A', strtotime($this->created_at))
        ];
    }
}
