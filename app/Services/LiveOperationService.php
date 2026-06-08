<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormElement;
use Illuminate\Support\Facades\DB;
use Auth;

class LiveOperationService
{
    public function list()
    {
        $user = Auth::user();
    }
}