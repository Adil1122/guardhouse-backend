<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteCheckpointResource extends JsonResource
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
            'name' => $this->name,
            'qr_code_token' => $this->qr_code_token,
            'geofence' => $this->geofence,
            'created_at' => date('Y-m-d H:i A', strtotime($this->created_at)),
        ];
    }
}
