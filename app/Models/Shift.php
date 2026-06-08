<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Events\ShiftCreated;
use App\Http\Resources\ShiftResource;

class Shift extends Model
{
    protected $fillable = [
        'site_id',
        'service_type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'timezone',
        'break_duration',
        'status',
        'customer_po',
        'service_group_id',
        'pay_group_id',
        'questionnaire',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public static function getResource()
    {
        return ShiftResource::class;
    }

    public $createdEvent = ShiftCreated::class;

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'site_id' => 'required|exists:sites,id',
                'service_type' => 'required|in:static-guard,mobile-patrol',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'start_time' => 'required|date_format:H:i:s',
                'end_time' => 'required|date_format:H:i:s',
                'timezone' => 'nullable|string',
                'break_duration' => 'nullable|integer|min:0',
                'status' => 'nullable|in:created,offered,confirmed,rejected,awaiting,checking-welfare,clocked-in,clocked-out,clocked-out-offsite,missed-alert,missed-clock-in',
                'customer_po' => 'nullable|string|max:30',
                'service_group_id' => 'nullable|exists:service_groups,id',
                'pay_group_id' => 'nullable|exists:pay_groups,id',
                'questionnaire' => 'nullable|in:global,site',
                'assigned_to' => 'nullable|exists:users,id',
                'created_by' => 'nullable|exists:users,id',
            ];
        } else if ($action === 'update') {
            $rules = [
                'site_id' => 'nullable|exists:sites,id',
                'service_type' => 'nullable|in:static-guard,mobile-patrol',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'start_time' => 'nullable|date_format:H:i:s',
                'end_time' => 'nullable|date_format:H:i:s',
                'timezone' => 'nullable|string',
                'break_duration' => 'nullable|integer|min:0',
                'status' => 'nullable|in:created,offered,confirmed,rejected,awaiting,checking-welfare,clocked-in,clocked-out,clocked-out-offsite,missed-alert,missed-clock-in',
                'customer_po' => 'nullable|string|max:30',
                'service_group_id' => 'nullable|exists:service_groups,id',
                'pay_group_id' => 'nullable|exists:pay_groups,id',
                'questionnaire' => 'nullable|in:global,site',
                'assigned_to' => 'nullable|exists:users,id',
                'created_by' => 'nullable|exists:users,id',
            ];
        }

        return $rules;
    }

    public function notes()
    {
        return $this->hasMany(ShiftNote::class);
    }

    public function timeclockLogs()
    {
        return $this->hasMany(ShiftTimeclockLog::class);
    }

    public function alerts()
    {
        return $this->hasMany(ShiftAlert::class);
    }

    public function questionnaireAnswers()
    {
        return $this->hasMany(ShiftClockInQuestionnaireAnswer::class);
    }

    public function checkpointScans()
    {
        return $this->hasMany(ShiftCheckpointScan::class);
    }

    public function formSubmissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function digitalOccurrenceLogs()
    {
        return $this->hasMany(DigitalOccurrenceLog::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function serviceGroup()
    {
        return $this->belongsTo(ServiceGroup::class, 'service_group_id');
    }

    public function payGroup()
    {
        return $this->belongsTo(PayGroup::class, 'pay_group_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function timesheet()
    {
        return $this->hasOne(Timesheet::class);
    }

    public function checkins()
    {
        return $this->hasMany(Checkin::class)->orderBy('checked_in_at', 'desc');
    }
}
