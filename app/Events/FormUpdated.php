<?php

namespace App\Events;

use App\Models\Form;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FormUpdated
{
    use Dispatchable, SerializesModels;

    public $form;
    public $validatedData;
    public $oldData;

    public function __construct(Form $form, array $validatedData, array $oldData)
    {
        $this->form = $form;
        $this->validatedData = $validatedData;
        $this->oldData = $oldData;
    }
}
