<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\ServiceGroup;
use App\Services\ServiceGroupService;

class ServiceGroupController extends BaseController
{
    protected $filterKey = null;
    protected $service;

    public function __construct(ServiceGroupService $service)
    {
        parent::__construct(new ServiceGroup());
        $this->service = $service;
    }
}
