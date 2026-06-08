<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\CustomerInvoiceProfileResource;
use App\Rules\AddressRule;

class CustomerInvoiceProfile extends Model
{
    protected $fillable = [
        'customer_profile_id',
        'company_name',
        'contact_first_name',
        'contact_last_name',
        'email',
        'contact_number',
        'po_number_prefix',
        'tax_rate',
        'export_invoice_type',
        'address',
        'terms',
    ];

    protected $casts = ['address' => 'array'];

    public function customerProfile()
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    public static function getResource()
    {
        return CustomerInvoiceProfileResource::class;
    }

    /**
     * Validation rules to be used for customer invoice profile create or update
     */
    public static function getValidationRules($action)
    {
        $rules = [
            'company_name' => 'required|string|max:50',
            'contact_first_name' => 'required|string|max:50',
            'contact_last_name' => 'required|string|max:50',
            'email' => 'nullable|email|max:30',
            'contact_number' => 'required|string|max:30',
            'po_number_prefix' => 'nullable|string|max:20',
            'tax_rate' => 'nullable|numeric|between:0,99999999.99',
            'export_invoice_type' => 'nullable|in:summary,detail',
            'address' => ['nullable', new AddressRule()],
            'terms' => 'nullable|string',
        ];

        if ($action === 'store') {
            $rules['customer_profile_id'] = 'required|exists:customer_profiles,id';
        } else if ($action === 'update') {
            
        }

        return $rules;
    }
}
