<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\TimesheetResource;
use App\Events\TimesheetUpdated; 

class Timesheet extends Model
{
    protected $fillable = [
        'shift_id',
        'entry_type',
        'site_id',
        'employee_user_id',
        'service_group_id',
        'pay_group_id',
        'start_date',
        'end_date',
        'timezone',
        'start_time',
        'end_time',
        'break_duration',
        'status',
        'service_total_amount',
        'service_breakdown',
        'notes',
    ];

    protected $casts = [
        'break_duration' => 'integer',
        'service_breakdown' => 'array',
        'service_total_amount' => 'decimal:2',
    ];

    public $updatedEvent = TimesheetUpdated::class;

    public static function getResource()
    {
        return TimesheetResource::class;
    }

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            
        } else if ($action === 'update') {
            $rules = [
                'entry_type' => 'nullable|in:work,leave',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'timezone' => 'nullable|string',
                'start_time' => 'nullable|date_format:H:i:s',
                'end_time' => 'nullable|date_format:H:i:s',
                'break_duration' => 'nullable|integer|min:0',
                'service_group_id' => 'nullable|exists:service_groups,id',
                'pay_group_id' => 'nullable|exists:pay_groups,id',
                'status' => 'nullable|in:drafted,approved,rejected,invoiced,paid,settled',
                'notes' => 'nullable|string',
            ];
        }

        return $rules;
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function employeeUser()
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function serviceGroup()
    {
        return $this->belongsTo(ServiceGroup::class, 'service_group_id');
    }

    public function payGroup()
    {
        return $this->belongsTo(PayGroup::class, 'pay_group_id');
    }
}
