<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\ServiceGroupResource;
use App\Events\ServiceGroupCreated;
use App\Events\ServiceGroupUpdated;
use App\Rules\RateRule;

class ServiceGroup extends Model
{
    protected $fillable = ['mode', 'name', 'base_rate'];

    public static function getResource()
    {
        return ServiceGroupResource::class;
    }

    public $createdEvent = ServiceGroupCreated::class;
    public $updatedEvent = ServiceGroupUpdated::class;

    /**
     * Validation rules to be used for service group create or update
     */
    public static function getValidationRules($action)
    {
        $rules = [
            'mode' => 'required|in:hourly,flat',
            'name' => 'required|string|max:50',
            'base_rate' => 'required|numeric|between:0,99999999.99',
            'rates' => 'required|array|min:1',
            'rates.*' => new RateRule(),
        ];

        if ($action === 'store') {

        } else if ($action === 'update') {
            $rules['mode'] = 'nullable|in:hourly,flat';
            $rules['name'] = 'nullable|string|max:50';
            $rules['base_rate'] = 'nullable|numeric|between:0,99999999.99';
            $rules['rates'] = 'nullable|array|min:1';
        }

        return $rules;
    }

    public function rates()
    {
        return $this->hasMany(ServiceRate::class);
    }
}
