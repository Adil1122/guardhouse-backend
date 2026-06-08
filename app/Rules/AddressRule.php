<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AddressRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return; 
        }
        
        if (!is_array($value)) {
            $fail('Address must be an array.');
            return;
        }

        $requiredKeys = ['name', 'city', 'state', 'zip', 'country'];
        $valueKeys = array_keys($value);

        sort($requiredKeys);
        sort($valueKeys);

        if ($requiredKeys !== $valueKeys) {
            $fail('Address must only contain name, city, state, zip and country.');
        }
    }
}
