<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormElement;
use Illuminate\Support\Facades\DB;

class FormService
{
    public function updateElementOrder(int $formId, array $data)
    {
        $elementId = $data['form_element_id'];
        $direction = $data['direction'];

        return DB::transaction(function () use ($formId, $elementId, $direction) {
            $form = Form::find($formId);
            if (!$form) {
                return ['success' => false, 'error' => 'Form not found', 'error_code' => 404];
            }

            $element = $form->elements()->find($elementId);
            if (!$element) {
                return ['success' => false, 'error' => 'Form element not found', 'error_code' => 404];
            }

            $elements = $form->elements()->orderBy('order')->get();
            $currentIndex = $elements->search(fn($item) => $item->id == $elementId);

            if ($direction === 'up' && $currentIndex > 0) {
                $swapWith = $elements[$currentIndex - 1];
                $tempOrder = $element->order;
                $element->order = $swapWith->order;
                $swapWith->order = $tempOrder;
                
                $element->save();
                $swapWith->save();
            } elseif ($direction === 'down' && $currentIndex < $elements->count() - 1) {
                $swapWith = $elements[$currentIndex + 1];
                $tempOrder = $element->order;
                $element->order = $swapWith->order;
                $swapWith->order = $tempOrder;

                $element->save();
                $swapWith->save();
            }

            $allElements = $form->elements()->orderBy('order')->get();
            foreach ($allElements as $index => $item) {
                $item->order = $index + 1;
                $item->save();
            }

            return ['success' => true];
        });
    }
}
