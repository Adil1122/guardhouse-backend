<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftTimeclockLog extends Model
{
    protected $fillable = [
        'shift_id',
        'clocked_in',
        'clocked_out',
        'work_duration',
        'location',
        'marked_by',
    ];

    protected $casts = [
        'location' => 'array',
    ];

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'shift_id' => 'required|exists:shifts,id',
                'action' => 'required|in:clock-in,clock-out',
                'clocked_in' => 'nullable|date|required_if:action,clock-in|prohibited_if:action,clock-out',
                'clocked_out' => 'nullable|date|required_if:action,clock-out|prohibited_if:action,clock-in',
                'work_duration' => 'nullable|numeric|min:0|required_if:action,clock-out',
                'location' => 'nullable|array',
                'marked_by' => 'nullable|exists:users,id',
            ];
        } else if ($action === 'update') {
            $rules = [];
        }

        return $rules;
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
