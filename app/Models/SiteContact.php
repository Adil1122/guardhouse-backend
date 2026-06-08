<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\SiteContactResource;

class SiteContact extends Model
{
    protected $fillable = [
        'site_id',
        'first_name',
        'last_name',
        'email',
        'contact_number',
        'position',
        'notes',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public static function getResource()
    {
        return SiteContactResource::class;
    }

    /**
     * Validation rules to be used for site contact create or update
     */
    public static function getValidationRules($action)
    {
        $rules = [
            'site_id' => 'required|exists:sites,id',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'nullable|email|max:30',
            'contact_number' => 'required|string|max:30',
            'position' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ];

        if ($action === 'store') {
            
        } else if ($action === 'update') {
            
        }

        return $rules;
    }
}
