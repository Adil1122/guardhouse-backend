<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\SiteResource;
use App\Rules\GeofenceRule;
use App\Rules\AddressRule;

class Site extends Model
{
    protected $fillable = [
        'type',
        'customer_profile_id',
        'name',
        'geofence',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'default_pay_group_id',
        'default_service_group_id',
        'custom_clockin_questionnaire',
        'instructions',
    ];

    protected $casts = [
        'geofence' => 'array',
        'custom_clockin_questionnaire' => 'array',
    ];

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'type' => 'required|in:static,mobile-patrol',
                'customer_profile_id' => 'nullable|exists:customer_profiles,id',
                'name' => 'required|string|max:100',
                'geofence' => ['required', new GeofenceRule()],
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'zip' => 'required|string|max:20',
                'country' => 'required|string|max:100',
                'default_pay_group_id' => 'nullable|exists:pay_groups,id',
                'default_service_group_id' => 'nullable|exists:service_groups,id',
                'custom_clockin_questionnaire' => 'nullable|array',
                'instructions' => 'nullable|string',
            ];
        } else if ($action == 'update') {
            $rules = [
                'type' => 'nullable|in:static,mobile-patrol',
                'customer_profile_id' => 'nullable|exists:customer_profiles,id',
                'name' => 'nullable|string|max:100',
                'geofence' => ['nullable', new GeofenceRule()],
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'zip' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'default_pay_group_id' => 'nullable|exists:pay_groups,id',
                'default_service_group_id' => 'nullable|exists:service_groups,id',
                'custom_clockin_questionnaire' => 'nullable|array',
                'instructions' => 'nullable|string',
            ];
        }

        return $rules;
    }

    public static function getResource()
    {
        return SiteResource::class;
    }

    public function customer()
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_profile_id');
    }

    public function defaultPayGroup()
    {
        return $this->belongsTo(PayGroup::class, 'default_pay_group_id');
    }

    public function defaultServiceGroup()
    {
        return $this->belongsTo(ServiceGroup::class, 'default_service_group_id');
    }

    public function contacts()
    {
        return $this->hasMany(SiteContact::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function checkpoints()
    {
        return $this->hasMany(SiteCheckpoint::class);
    }
}
