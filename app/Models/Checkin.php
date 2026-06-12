<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\CheckinResource;

class Checkin extends Model
{
    protected $fillable = [
        'shift_id',
        'site_checkpoint_id',
        'user_id',
        'latitude',
        'longitude',
        'location_description',
        'notes',
        'type',
        'photo_path',
        'photo_lat',
        'photo_lng',
        'checked_in_at',
        'checked_out_at',
        'status',
        'inside_geofence',
        'distance_from_site',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'photo_lat' => 'decimal:8',
        'photo_lng' => 'decimal:8',
        'inside_geofence' => 'boolean',
        'distance_from_site' => 'decimal:2',
    ];

    public static function getResource()
    {
        return CheckinResource::class;
    }

    public static function getValidationRules($action)
    {
        if ($action === 'store') {
            $rules = [
                'shift_id' => 'required|exists:shifts,id',
                'site_checkpoint_id' => 'nullable|exists:site_checkpoints,id',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'location_description' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:1000',
                'type' => 'nullable|in:regular,incident,checkpoint',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
                'inside_geofence' => 'nullable|boolean',
                'distance_from_site' => 'nullable|numeric|min:0',
            ];
        } else if ($action === 'update') {
            $rules = [
                'site_checkpoint_id' => 'nullable|exists:site_checkpoints,id',
                'location_description' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:1000',
                'type' => 'nullable|in:regular,incident,checkpoint',
            ];
        }

        return $rules;
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function checkpoint()
    {
        return $this->belongsTo(SiteCheckpoint::class, 'site_checkpoint_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate distance from site geofence center using Haversine formula
     */
    public function calculateDistanceFromSite($siteGeofence)
    {
        if (!$siteGeofence || !isset($siteGeofence['lat'], $siteGeofence['lon'])) {
            return null;
        }

        $earthRadius = 6371000; // Earth's radius in meters

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($siteGeofence['lat']);
        $lonTo = deg2rad($siteGeofence['lon']);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Check if checkin is within site geofence
     */
    public function isWithinGeofence($siteGeofence)
    {
        $distance = $this->calculateDistanceFromSite($siteGeofence);
        $checkInDistance = $siteGeofence['check_in_distance'] ?? 100; // Default 100m

        return $distance !== null && $distance <= $checkInDistance;
    }
}
