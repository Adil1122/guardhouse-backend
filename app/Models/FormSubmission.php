<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_id',
        'shift_id',
    ];

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'form_id' => 'required|exists:forms,id',
                'shift_id' => 'required|exists:shifts,id',
            ];
        } else if ($action === 'update') {
            $rules = [
                'form_id' => 'nullable|exists:forms,id',
                'shift_id' => 'nullable|exists:shifts,id',
            ];
        }

        return $rules;
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function details()
    {
        return $this->hasMany(FormSubmissionDetail::class, 'form_submission_id');
    }
}
