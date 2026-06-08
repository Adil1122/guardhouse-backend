<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return is_string($value) &&
               strlen($value) >= 8 &&
               preg_match('/[A-Za-z]/', $value) && // At least one letter
               preg_match('/\d/', $value) &&       // At least one number
               preg_match('/[@$!%*?&]/', $value);  // At least one special character
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be at least 8 characters long, contain at least one letter, one number, and one special character.';
    }
}
