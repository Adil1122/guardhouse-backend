<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
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
            'type' => $this->type,
            'customer_profile_id' => $this->customer_profile_id,
            'name' => $this->name,
            'geofence' => $this->geofence,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
            'default_pay_group_id' => $this->default_pay_group_id,
            'default_service_group_id' => $this->default_service_group_id,
            'custom_clockin_questionnaire' => $this->custom_clockin_questionnaire,
            'instructions' => $this->instructions,
            'created_at' => date('Y-m-d H:i A', strtotime($this->created_at))
        ];
    }
}
