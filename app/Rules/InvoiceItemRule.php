<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Timesheet;

class InvoiceItemRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('Invalid invoice item format.');
            return;
        }

        $type = $value['reference_type'] ?? null;

        if (!$type) {
            $fail('reference_type is required.');
            return;
        }

        if ($type === 'timesheet') {
            $referenceId = $value['reference_id'] ?? null;

            if (!$referenceId) {
                $fail('reference_id is required for timesheet.');
                return;
            }

            $timesheet = Timesheet::find($referenceId);

            if (!$timesheet) {
                $fail('Timesheet not found.');
                return;
            }

            if (!in_array($timesheet->status, ['approved', 'invoiced'])) {
                $fail('Timesheet must be approved.');
            }

        } elseif ($type === 'manual-billable') {

            $requiredFields = ['date', 'name', 'rate', 'units', 'total_amount'];

            foreach ($requiredFields as $field) {
                if (!isset($value[$field]) || $value[$field] === '') {
                    $fail("The {$field} field is required for manual billable item.");
                }
            }

            if (isset($value['rate']) && !is_numeric($value['rate'])) {
                $fail('Rate must be numeric.');
            }

            if (isset($value['units']) && !is_numeric($value['units'])) {
                $fail('Units must be numeric.');
            }

            if (isset($value['total_amount']) && !is_numeric($value['total_amount'])) {
                $fail('Total amount must be numeric.');
            }

        } else {
            $fail('Invalid reference_type.');
        }
    }
}

