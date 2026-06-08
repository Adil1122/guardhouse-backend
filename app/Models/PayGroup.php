<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\PayGroupResource;
use App\Events\PayGroupCreated;
use App\Events\PayGroupUpdated;
use App\Rules\RateRule;

class PayGroup extends Model
{
    protected $fillable = ['mode', 'name', 'base_rate'];

    public $createdEvent = PayGroupCreated::class;
    public $updatedEvent = PayGroupUpdated::class;

    public static function getResource()
    {
        return PayGroupResource::class;
    }

    /**
     * Validation rules to be used for pay group create or update
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
        return $this->hasMany(PayRate::class);
    }
}
