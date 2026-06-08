<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Models\SiteAccessCode;
use App\Services\SiteAccessCodeService;

class SiteAccessCodeController extends BaseController
{
    protected $filterKey = null;
    protected $service;

    public function __construct(SiteAccessCodeService $service)
    {
        parent::__construct(new SiteAccessCode());
        $this->service = $service;
    }
}
