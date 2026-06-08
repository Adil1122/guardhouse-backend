<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Services\OrganizationComplianceService;
use App\Models\OrganizationCompliance;

class ComplianceController extends BaseController
{
    protected $service;
    protected $filterKey = null;

    public function __construct(OrganizationComplianceService $service)
    {
        parent::__construct(new OrganizationCompliance());
        $this->service = $service;
    }

    public function index(Request $request)
    {
        if (OrganizationCompliance::count() === 0) {
            $defaults = [
                ['name' => 'SIA License', 'remind_in_days' => 30, 'is_critical' => true, 'show_to_customer' => false],
                ['name' => 'Right To Work', 'remind_in_days' => 30, 'is_critical' => true, 'show_to_customer' => false],
                ['name' => 'First Aid Certificate', 'remind_in_days' => 30, 'is_critical' => false, 'show_to_customer' => false],
            ];

            foreach ($defaults as $default) {
                OrganizationCompliance::firstOrCreate(['name' => $default['name']], $default);
            }
        }

        return parent::index($request);
    }
}
