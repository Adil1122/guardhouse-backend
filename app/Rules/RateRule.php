<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RateRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The rate must be an array.');
            return;
        }

        if (!isset($value['days']) || !is_array($value['days']) || count($value['days']) === 0) {
            $fail('The days field is required and must be an array containing at least one day.');
            return;
        }

        $validDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        foreach ($value['days'] as $day) {
            if (!in_array($day, $validDays)) {
                $fail("Invalid day: {$day}. Valid days are mon, tue, wed, thu, fri, sat, sun.");
                return;
            }
        }

        if (!isset($value['from_time']) || $value['from_time'] === '') {
            $fail('The from_time field is required.');
            return;
        }

        if (!isset($value['to_time']) || $value['to_time'] === '') {
            $fail('The to_time field is required.');
            return;
        }

        if (!isset($value['rate']) || $value['rate'] === '') {
            $fail('The rate field is required.');
            return;
        }

        if (!is_numeric($value['rate'])) {
            $fail('The rate must be numeric.');
            return;
        }
    }
}
