<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetResource extends JsonResource
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
            'entry_type' => $this->entry_type,
            'site' => $this->site ? ['id' => $this->site->id, 'name' => $this->site->name] : null,
            'customer' => $this->site?->customer?->user ? ['id' => $this->site->customer->user->id, 'name' => $this->site->customer->user->first_name . ' ' . $this->site->customer->user->last_name] : null,
            'employee' => $this->employeeUser ? ['id' => $this->employeeUser->id, 'name' => $this->employeeUser->first_name . ' ' . $this->employeeUser->last_name] : null,
            'start_date' => $this->shift ? date('d/m/Y', strtotime($this->shift->start_date)) : null,
            'end_date' => $this->shift ? date('d/m/Y', strtotime($this->shift->end_date)) : null,
            'service_detail' => [
                'total_amount' => $this->service_total_amount,
                'breakdown' => $this->service_breakdown,
                'group' => $this->serviceGroup ? ['id' => $this->serviceGroup->id, 'name' => $this->serviceGroup->name] : null
            ],
            'pay_detail' => [
                'group' => $this->payGroup ? ['id' => $this->payGroup->id, 'name' => $this->payGroup->name] : null,
            ],
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'break_duration' => $this->break_duration,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at ? date('Y-m-d H:i A', strtotime($this->created_at)) : null,
        ];
    }
}
