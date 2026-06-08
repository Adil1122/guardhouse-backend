<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\CustomerContactResource;

class CustomerContact extends Model
{
    protected $fillable = [
        'customer_profile_id',
        'first_name',
        'last_name',
        'email',
        'contact_number',
        'position',
        'notes',
    ];

    public function customerProfile()
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    public static function getResource()
    {
        return CustomerContactResource::class;
    }

    /**
     * Validation rules to be used for customer contact create or update
     */
    public static function getValidationRules($action)
    {
        $rules = [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'nullable|email|max:30',
            'contact_number' => 'required|string|max:30',
            'position' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ];

        if ($action === 'store') {
            $rules['customer_profile_id'] = 'required|exists:customer_profiles,id';
        } else if ($action === 'update') {

        }

        return $rules;
    }
}
