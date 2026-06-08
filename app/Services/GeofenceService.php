<?php

namespace App\Services;

class GeofenceService
{
    /**
     * Calculate distance between two points using Haversine formula
     * 
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in meters
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

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
     * Check if a point is within a geofence
     * 
     * @param float $latitude Point latitude
     * @param float $longitude Point longitude
     * @param array $geofence Geofence data with lat, lon, and check_in_distance
     * @return bool True if within geofence
     */
    public function isWithinGeofence($latitude, $longitude, $geofence)
    {
        if (!$geofence || !isset($geofence['lat'], $geofence['lon'])) {
            return false;
        }

        $distance = $this->calculateDistance(
            $latitude, 
            $longitude, 
            $geofence['lat'], 
            $geofence['lon']
        );

        $checkInDistance = $geofence['check_in_distance'] ?? 100; // Default 100m

        return $distance <= $checkInDistance;
    }

    /**
     * Get geofence status with distance information
     * 
     * @param float $latitude Point latitude
     * @param float $longitude Point longitude
     * @param array $geofence Geofence data
     * @return array Geofence status information
     */
    public function getGeofenceStatus($latitude, $longitude, $geofence)
    {
        if (!$geofence || !isset($geofence['lat'], $geofence['lon'])) {
            return [
                'inside_geofence' => false,
                'distance_from_site' => null,
                'check_in_distance' => null,
                'status' => 'invalid_geofence'
            ];
        }

        $distance = $this->calculateDistance(
            $latitude, 
            $longitude, 
            $geofence['lat'], 
            $geofence['lon']
        );

        $checkInDistance = $geofence['check_in_distance'] ?? 100;
        $insideGeofence = $distance <= $checkInDistance;

        return [
            'inside_geofence' => $insideGeofence,
            'distance_from_site' => $distance,
            'check_in_distance' => $checkInDistance,
            'status' => $insideGeofence ? 'inside' : 'outside'
        ];
    }

    /**
     * Validate geofence data structure
     * 
     * @param array $geofence Geofence data to validate
     * @return bool True if valid
     */
    public function validateGeofence($geofence)
    {
        if (!is_array($geofence)) {
            return false;
        }

        $requiredFields = ['lat', 'lon'];
        foreach ($requiredFields as $field) {
            if (!isset($geofence[$field]) || !is_numeric($geofence[$field])) {
                return false;
            }
        }

        // Validate latitude range
        if ($geofence['lat'] < -90 || $geofence['lat'] > 90) {
            return false;
        }

        // Validate longitude range
        if ($geofence['lon'] < -180 || $geofence['lon'] > 180) {
            return false;
        }

        // Validate check_in_distance if present
        if (isset($geofence['check_in_distance'])) {
            if (!is_numeric($geofence['check_in_distance']) || $geofence['check_in_distance'] < 0) {
                return false;
            }
        }

        return true;
    }
}
