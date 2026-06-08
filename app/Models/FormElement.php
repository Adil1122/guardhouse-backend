<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormElement extends Model
{
    use HasFactory;

    protected $fillable = ['form_id', 'type', 'title', 'order'];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
