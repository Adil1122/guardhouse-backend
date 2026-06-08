<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Services\SitePreferenceService;
use App\Models\SitePreference;

class SitePreferenceController extends BaseController
{
    protected $filterKey = null;
    protected $service;

    public function __construct(SitePreferenceService $service)
    {
        parent::__construct(new SitePreference());
        $this->service = $service;
    }
}
