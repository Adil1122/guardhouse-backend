<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftClockInQuestionnaireAnswer extends Model
{
    protected $fillable = [
        'shift_id',
        'questions_answers',
    ];

    protected $casts = [
        'questions_answers' => 'array',
    ];

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'shift_id' => 'required|exists:shifts,id',
                'questions_answers' => 'required|array',
            ];
        } else if ($action === 'update') {
            $rules = [
                'shift_id' => 'nullable|exists:shifts,id',
                'questions_answers' => 'nullable|array',
            ];
        }

        return $rules;
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
