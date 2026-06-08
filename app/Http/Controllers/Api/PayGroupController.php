<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\PayGroup;
use App\Models\PayRate;
use App\Services\PayGroupService;

class PayGroupController extends BaseController
{
    protected $filterKey = null;
    protected $service;

    public function __construct(PayGroupService $service)
    {
        parent::__construct(new PayGroup());
        $this->service = $service;
    }

    public function index(Request $request)
    {
        if (PayGroup::count() === 0) {
            $defaults = [
                ['name' => 'Standard Hourly', 'mode' => 'hourly', 'base_rate' => 15.00],
                ['name' => 'Night Shift', 'mode' => 'hourly', 'base_rate' => 18.00],
            ];

            foreach ($defaults as $default) {
                $payGroup = PayGroup::firstOrCreate(['name' => $default['name']], $default);
                PayRate::firstOrCreate(
                    [
                        'pay_group_id' => $payGroup->id,
                        'from_time' => '00:00:00',
                        'to_time' => '23:59:59',
                    ],
                    [
                        'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                        'rate' => $payGroup->base_rate,
                    ]
                );
            }
        }

        return parent::index($request);
    }
}
