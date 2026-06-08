<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $site = $this->site;
        $customerName = null;
        if ($site && $site->customer) {
            $customerUser = $site->customer->user;
            $customerName = $customerUser->first_name . ' ' . $customerUser->last_name;
        }
        
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'site' => $site ? ['id' => $this->site_id, 'name' => $site->name] : null,
            'customer_name' => $customerName,
            'service_type' => $this->service_type,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'timezone' => $this->timezone,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'break_duration' => $this->break_duration,
            'status' => $this->status,
            'customer_po' => $this->customer_po,
            'service_group' => $this->serviceGroup ? ['id' => $this->serviceGroup->id, 'name' => $this->serviceGroup->name] : null,
            'service_group_id' => $this->service_group_id,
            'pay_group' => $this->payGroup ? ['id' => $this->payGroup->id, 'name' => $this->payGroup->name] : null,
            'pay_group_id' => $this->pay_group_id,
            'questionnaire' => $this->questionnaire,
            'assigned_to' => $this->assignedUser ? ['id' => $this->assignedUser->id, 'name' => $this->assignedUser->first_name . ' ' . $this->assignedUser->last_name] : null,
            'assigned_to_id' => $this->assigned_to,
            'created_by' => $this->createdBy ? ['id' => $this->createdBy->id, 'name' => $this->createdBy->first_name . ' ' . $this->createdBy->last_name] : null,
            'created_by_id' => $this->created_by,
            'created_at' => $this->created_at ? date('Y-m-d H:i A', strtotime($this->created_at)) : null,
        ];
    }
}
