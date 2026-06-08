<?php

namespace App\Listeners;

use App\Events\FormCreated;

class HandleFormCreated
{
    public function handle(FormCreated $event): void
    {
        $form = $event->form;
        $elements = request()->input('elements', []);

        if (!empty($elements) && is_array($elements)) {
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
