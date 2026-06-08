<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FormElementRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The :attribute must be an array.');
            return;
        }

        $validTypes = ['text', 'textarea', 'date', 'time', 'file', 'boolean', 'signature'];

        foreach ($value as $index => $element) {
            if (!isset($element['type']) || !in_array($element['type'], $validTypes)) {
                $fail("The element at index {$index} has an invalid type.");
            }

            if (!isset($element['title']) || empty($element['title'])) {
                $fail("The element at index {$index} must have a title.");
            }

            if (strlen($element['title']) > 30) {
                $fail("The element title at index {$index} must not exceed 30 characters.");
            }
        }
    }
}
