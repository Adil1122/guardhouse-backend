<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteAccessCodeResource extends JsonResource
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
            'site' => $site ? [
                'id' => $site->id,
                'name' => $site->name,
            ] : null,
            'name' => $this->name,
            'code' => $this->code,
            'notes' => $this->notes,
            'offsite_visibility' => $this->offsite_visibility,
            'created_at' => $this->created_at ? date('Y-m-d H:i A', strtotime($this->created_at)) : null,
        ];
    }
}
