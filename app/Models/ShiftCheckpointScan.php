<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftCheckpointScan extends Model
{
    protected $fillable = [
        'shift_id',
        'site_checkpoint_id',
    ];

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'shift_id' => 'required|exists:shifts,id',
                'site_checkpoint_id' => 'required|exists:site_checkpoints,id',
            ];
        } else if ($action === 'update') {
            $rules = [
                'shift_id' => 'nullable|exists:shifts,id',
                'site_checkpoint_id' => 'nullable|exists:site_checkpoints,id',
            ];
        }

        return $rules;
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function checkpoint()
    {
        return $this->belongsTo(SiteCheckpoint::class, 'site_checkpoint_id');
    }
}
