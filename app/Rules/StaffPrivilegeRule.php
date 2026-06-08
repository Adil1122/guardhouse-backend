<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StaffPrivilegeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('Privileges must be an array.');
            return;
        }

        // Merge all allowed items from all roles without overriding
        // overlapping keys; each item gets a union of all allowed levels.
        $allAllowedItems = [];
        $staffPrivileges = config('constants.staff_privileges', []);
        foreach ($staffPrivileges as $roleItems) {
            foreach ($roleItems as $item => $levels) {
                if (!isset($allAllowedItems[$item])) {
                    $allAllowedItems[$item] = [];
                }
                $allAllowedItems[$item] = array_values(
                    array_unique(array_merge($allAllowedItems[$item], $levels))
                );
            }
        }

        foreach ($value as $item => $privileges) {
            // Skip validation for items not in config (allow custom items)
            if (!isset($allAllowedItems[$item])) {
                continue;
            }

            if (!is_array($privileges)) {
                $fail("Privileges for {$item} must be an array.");
                continue;
            }

            $allowedForThisItem = $allAllowedItems[$item] ?? [];

            foreach ($privileges as $priv) {
                if (!in_array($priv, $allowedForThisItem)) {
                    $fail("Invalid privilege: {$priv} for item {$item}");
                }
            }
        }
    }
}
