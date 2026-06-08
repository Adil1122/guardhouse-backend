<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffComplianceResource extends JsonResource
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
            'staff_profile_id' => $this->staff_profile_id,
            'compliance' => ['id' => $this->compliance_id, 'name' => $this->compliance ? $this->compliance->name : ''],
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'files' => $this->files,
            'created_at' => date('Y-m-d H:i A', strtotime($this->created_at))
        ];
    }
}
