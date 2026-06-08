<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckinResource extends JsonResource
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
            'shift_id' => $this->shift_id,
            'site_checkpoint_id' => $this->site_checkpoint_id,
            'user_id' => $this->user_id,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'location_description' => $this->location_description,
            'notes' => $this->notes,
            'type' => $this->type,
            'photo_path' => $this->photo_path,
            'photo_lat' => $this->photo_lat ? (float) $this->photo_lat : null,
            'photo_lng' => $this->photo_lng ? (float) $this->photo_lng : null,
            'checked_in_at' => $this->checked_in_at,
            'inside_geofence' => $this->inside_geofence,
            'distance_from_site' => $this->distance_from_site,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'checkpoint' => $this->when($this->site_checkpoint_id, function () {
                return [
                    'id' => $this->checkpoint->id,
                    'name' => $this->checkpoint->name,
                ];
            }),
            
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->first_name . ' ' . $this->user->last_name,
            ],
        ];
    }
}
