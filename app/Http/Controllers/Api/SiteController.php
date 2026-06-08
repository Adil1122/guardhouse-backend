<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Site;
use App\Services\SiteService;
use Illuminate\Http\Request;

class SiteController extends BaseController
{
    protected $service;
    protected $filterKey = null;

    public function __construct(SiteService $service)
    {
        parent::__construct(new Site());
        $this->service = $service;
    }
}
