<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\ShiftNoteResource;

class ShiftNote extends Model
{
    protected $fillable = [
        'shift_id',
        'type',
        'type_details',
        'notes',
    ];

    protected $casts = [
        'type_details' => 'array',
    ];

    public static function getResource()
    {
        return ShiftNoteResource::class;
    }

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'shift_id' => 'required|exists:shifts,id',
                'type' => 'required|in:short_note,customer,internal,invoice,position',
                'type_details' => 'nullable|array',
                'notes' => 'nullable|string',
            ];
        } else if ($action === 'update') {
            $rules = [
                'shift_id' => 'nullable|exists:shifts,id',
                'type' => 'nullable|in:short_note,customer,internal,invoice,position',
                'type_details' => 'nullable|array',
                'notes' => 'nullable|string',
            ];
        }

        return $rules;
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
