<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Http\Resources\OrganizationComplianceResource;

class OrganizationCompliance extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'remind_in_days', 'is_critical', 'show_to_customer'];

    /**
     * Validation rules to be used for advertiser create or update
     */
    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules['name'] = 'required|string|max:255';
            $rules['remind_in_days'] = 'nullable|integer';
            $rules['is_critical'] = 'nullable|boolean';
            $rules['show_to_customer'] = 'nullable|boolean';
        } else if ($action == 'update') {
            $rules['name'] = 'nullable|string|max:255';
            $rules['remind_in_days'] = 'nullable|integer';
            $rules['is_critical'] = 'nullable|boolean';
            $rules['show_to_customer'] = 'nullable|boolean';    
        }

        return $rules;
    }

    public function staffCompliances()
    {
        return $this->hasMany(StaffCompliance::class, 'compliance_id');
    }

    public static function getResource()
    {
        return OrganizationComplianceResource::class;
    }
}
