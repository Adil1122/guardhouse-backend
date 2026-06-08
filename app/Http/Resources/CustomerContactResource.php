<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customerProfile = $this->customerProfile;

        return [
            'id' => $this->id,
            'customer' => [
                'id' => $customerProfile->user->id,
                'profile_id' => $customerProfile->id,
                'first_name' => $customerProfile->user->first_name,
                'last_name' => $customerProfile->user->last_name,
                'email' => $customerProfile->user->email,
            ],
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'position' => $this->position,
            'notes' => $this->notes,
            'created_at' => date('Y-m-d H:i A', strtotime($this->created_at)),
        ];
    }
}
