<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class OrganizationSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {   
        return [
            'two_factor_auth' => $this->two_factor_auth,
            'live_ops_sorting' => $this->live_ops_sorting,
            'custom_clockin_questionnaire' => $this->custom_clockin_questionnaire,
            'shift_alert_response_time' => $this->shift_alert_response_time,
            'default_pay_group_id' => $this->default_pay_group_id,
            'default_service_group_id' => $this->default_service_group_id,
            'genfence_check_in_distance' => $this->genfence_check_in_distance,
            'enable_digital_occurrence_logs' => $this->enable_digital_occurrence_logs,
            'updated_by' => UserResource::make($this->updatedBy),
        ];
    }
}
