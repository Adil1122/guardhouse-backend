<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;
        
        return [
            'profile_id' => $this->id,
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'status' => $user->status,
            'image' => $user->image,
            'reference_number' => $this->reference_number,
            'address' => $this->address,
            'default_service_group_id' => $this->default_service_group_id,
            'email_verified_at' => $user->email_verified_at
        ];
    }
}
