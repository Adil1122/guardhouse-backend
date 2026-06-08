<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\CustomerProfileResource;
use App\Rules\AddressRule;

class CustomerProfile extends Model
{
    protected $fillable = ['user_id', 'reference_number', 'address', 'default_service_group_id'];
    protected $casts = ['address' => 'array'];

    /**
     * Validation rules to be used for advertiser create or update
     */
    public static function getValidationRules($action)
    {
        $rules['first_name'] = 'required|string|max:50';
        $rules['last_name'] = 'required|string|max:50';
        $rules['reference_number'] = 'nullable|string|max:20';
        $rules['default_service_group_id'] = 'nullable|exists:service_groups,id';
        $rules['address'] = ['nullable', new AddressRule()];
        
        if ($action === 'store') {
            $rules['email'] = 'required|email|unique:users,email';
        } else if ($action == 'update') {
            
        }

        return $rules;
    }

    public static function getResource()
    {
        return CustomerProfileResource::class;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function defaultServiceGroup()
    {
        return $this->belongsTo(ServiceGroup::class, 'default_service_group_id');
    }

    public function contacts()
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function invoiceProfiles()
    {
        return $this->hasMany(CustomerInvoiceProfile::class);
    }
}
