<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmissionDetail extends Model
{
    protected $fillable = [
        'form_submission_id',
        'submission',
    ];

    protected $casts = [
        'submission' => 'array',
    ];

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'form_submission_id' => 'required|exists:form_submissions,id',
                'submission' => 'required|array',
            ];
        } else if ($action === 'update') {
            $rules = [
                'form_submission_id' => 'nullable|exists:form_submissions,id',
                'submission' => 'nullable|array',
            ];
        }

        return $rules;
    }

    public function formSubmission()
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }
}
