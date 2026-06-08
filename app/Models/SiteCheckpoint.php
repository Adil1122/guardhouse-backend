<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Rules\GeofenceRule;
use App\Http\Resources\SiteCheckpointResource;

class SiteCheckpoint extends Model
{
    protected $fillable = ['site_id', 'name', 'geofence', 'qr_code_token'];

    protected $casts = ['geofence' => 'array'];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public static function getResource()
    {
        return SiteCheckpointResource::class;
    }

    /**
     * Validation rules to be used for site contact create or update
     */
    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'site_id' => 'required|exists:sites,id',
                'name' => 'required|string|max:50',
                'geofence' => ['required', new GeofenceRule()],
                'qr_code_token' => 'required|string|max:255',
            ];
        } else if ($action === 'update') {
            $rules = [
                'site_id' => 'required|exists:sites,id',
                'name' => 'nullable|string|max:50',
                'geofence' => ['nullable', new GeofenceRule()],
                'qr_code_token' => 'nullable|string|max:255',
            ];
        }

        return $rules;
    }
}
