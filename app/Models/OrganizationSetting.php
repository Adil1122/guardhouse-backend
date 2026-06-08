<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\OrganizationSettingResource;

class OrganizationSetting extends Model
{
    public const UPDATED_AT = 'updated_at';
    public const CREATED_AT = null;

    public static function getResource()
    {
        return OrganizationSettingResource::class;
    }

    public function getValidationRules($action)
    {
        return [
            'two_factor_auth' => 'sometimes|boolean',
            'live_ops_sorting' => 'sometimes|in:time-asc,time-desc,duration-asc,duration-desc',
            'custom_clockin_questionnaire' => 'nullable|array',
            'custom_clockin_questionnaire.*' => 'required|string',
            'shift_alert_response_time' => 'nullable|integer',
            'default_pay_group_id' => 'nullable|exists:pay_groups,id',
            'default_service_group_id' => 'nullable|exists:service_groups,id',
            'genfence_check_in_distance' => 'sometimes|integer',
            'enable_digital_occurrence_logs' => 'sometimes|boolean',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'two_factor_auth',
        'live_ops_sorting',
        'custom_clockin_questionnaire',
        'shift_alert_response_time',
        'default_pay_group_id',
        'default_service_group_id',
        'genfence_check_in_distance',
        'enable_digital_occurrence_logs',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'two_factor_auth' => 'boolean',
        'enable_digital_occurrence_logs' => 'boolean',
        'custom_clockin_questionnaire' => 'array',
        'shift_alert_response_time' => 'integer',
        'genfence_check_in_distance' => 'integer',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who updated the settings.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
