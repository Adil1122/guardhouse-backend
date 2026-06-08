<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BankDetailRule implements ValidationRule
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
            $fail('Bank details must be an array.');
            return;
        }

        $requiredKeys = ['bank_name', 'account_name', 'account_number', 'bank_country'];
        $valueKeys = array_keys($value);

        sort($requiredKeys);
        sort($valueKeys);

        if ($requiredKeys !== $valueKeys) {
            $fail('Bank details must only contain bank_name, account_name, account_number, and bank_country.');
        }
    }
}
