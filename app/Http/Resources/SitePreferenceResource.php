<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\FormResource;

class SitePreferenceResource extends JsonResource
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
            'site_id' => $this->site_id,
            'reference_id' => $this->reference_id,
            'mode' => $this->mode,
            'setting' => $this->setting,
            'staff' => $this->when($this->mode === 'staff-setting', function () {
                return UserResource::make($this->staffUser);
            }),
            'form' => $this->when($this->mode === 'form-setting', function () {
                return FormResource::make($this->form);
            }),
            'created_at' => date('Y-m-d H:i A', strtotime($this->created_at))
        ];
    }
}
