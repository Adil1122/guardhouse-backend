<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\FormCreated;
use App\Events\FormUpdated; 
use App\Http\Resources\FormResource;
use App\Rules\FormElementRule;

class Form extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'name'];

    public $createdEvent = FormCreated::class;
    public $updatedEvent = FormUpdated::class;
    public $skipCreatedEvent = true;
    public $skipUpdatedEvent = true;

    public static function scopeApplyFilter($query, $filter, $filterId)
    {
        if (empty($filter) || $filter === 'all') {
            return $query;
        }

        $query->where('type', $filter);
        return $query;
    }

    public static function getResource()
    {
        return FormResource::class;
    }

    public function getValidationRules($action)
    {
        return [
            'name' => 'required|string|max:50',
            'type' => 'required|in:incident-report,welfare-check-report,maintaince-report,security-breach,mental-wellness-staff-audit',
            'elements' => ['nullable', 'array', new FormElementRule()],
        ];
    }

    public function elements()
    {
        return $this->hasMany(FormElement::class)->orderBy('order');
    }
}
