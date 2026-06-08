<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GeofenceRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('Geofence must be an array.');
            return;
        }

        $requiredKeys = ['place_id', 'lat', 'lon', 'check_in_distance'];
        $valueKeys = array_keys($value);

        sort($requiredKeys);
        sort($valueKeys);

        if ($requiredKeys !== $valueKeys) {
            $fail('Geofance must only contain place id, lat, lon and check in distance.');
        }
    }
}
