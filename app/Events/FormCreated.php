<?php

namespace App\Events;

use App\Models\Form;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FormCreated
{
    use Dispatchable, SerializesModels;

    public $form;

    public function __construct(Form $form)
    {
        $this->form = $form;
    }
}
