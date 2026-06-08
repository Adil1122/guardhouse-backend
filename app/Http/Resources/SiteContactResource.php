<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $site = $this->site;

        return [
            'id' => $this->id,
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
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
