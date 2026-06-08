<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\SiteCheckpoint;
use App\Services\SiteCheckpointService;

class SiteCheckpointController extends BaseController
{
    protected $filterKey = null;
    protected $service;

    public function __construct(SiteCheckpointService $service)
    {
        parent::__construct(new SiteCheckpoint());
        $this->service = $service;
    }
}
