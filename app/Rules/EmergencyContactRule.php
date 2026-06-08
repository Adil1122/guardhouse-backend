<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmergencyContactRule implements ValidationRule
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
            $fail('Emergency contact must be an array.');
            return;
        }

        $requiredKeys = ['relationship', 'name', 'contact_number'];
        $valueKeys = array_keys($value);

        sort($requiredKeys);
        sort($valueKeys);

        if ($requiredKeys !== $valueKeys) {
            $fail('Emergency contact must only contain relationship, name, and contact_number.');
        }
    }
}
