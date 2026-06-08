<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'status' => $this->status,
            'image' => $this->image ? secure_asset('public/storage/' . $this->image) : null,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => date('Y-m-d H:i A', strtotime($this->created_at)),
        ];
    }
}
