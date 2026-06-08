<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftAlert extends Model
{
    protected $fillable = [
        'shift_id',
        'user_id',
        'type',
        'response_status',
        'response_timestamp',
    ];

    protected $casts = [
        'response_timestamp' => 'datetime',
    ];

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'shift_id' => 'required|exists:shifts,id',
                'user_id' => 'required|exists:users,id',
                'type' => 'required|in:in-app-notification',
                'response_status' => 'nullable|in:replied,missed',
                'response_timestamp' => 'nullable|date',
            ];
        } else if ($action === 'update') {
            $rules = [
                'shift_id' => 'nullable|exists:shifts,id',
                'user_id' => 'nullable|exists:users,id',
                'type' => 'nullable|in:in-app-notification',
                'response_status' => 'nullable|in:replied,missed',
                'response_timestamp' => 'nullable|date',
            ];
        }

        return $rules;
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
