<?php

namespace App\Listeners;

use App\Events\FormUpdated;

class HandleFormUpdated
{
    public function handle(FormUpdated $event): void
    {
        $form = $event->form;
        $elements = request()->input('elements', []);

        if (!empty($elements) && is_array($elements)) {
            $form->elements()->delete();

            foreach ($elements as $index => $element) {
                if (isset($element['type']) && isset($element['title'])) {
                    $form->elements()->create([
                        'type' => $element['type'],
                        'title' => $element['title'],
                        'order' => $element['order'] ?? ($index + 1),
                    ]);
                }
            }
        }
    }
}
