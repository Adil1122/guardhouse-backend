<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\DigitalOccurrenceLogResource;

class DigitalOccurrenceLog extends Model
{
    protected $fillable = [
        'shift_id',
        'type',
        'description',
        'files',
        'show_to_customer',
        'created_by',
    ];

    protected $casts = [
        'files' => 'array',
        'show_to_customer' => 'boolean',
    ];

    public static function getResource()
    {
        return DigitalOccurrenceLogResource::class;
    }

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'shift_id' => 'required|exists:shifts,id',
                'type' => 'required|in:clock_in,clock_out,qr_scan,welfare_check_missed_alert,low_battery,emergency',
                'description' => 'nullable|string',
                'files' => 'nullable|array',
                'show_to_customer' => 'nullable|boolean',
                'created_by' => 'nullable|exists:users,id',
            ];
        } else if ($action === 'update') {
            $rules = [
                'show_to_customer' => 'required|boolean'
            ];
        }

        return $rules;
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
