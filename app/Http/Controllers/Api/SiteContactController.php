<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\SiteContact;
use App\Services\SiteContactService;

class SiteContactController extends BaseController
{
    protected $filterKey = 'site_id';
    protected $service;

    public function __construct(SiteContactService $service)
    {
        parent::__construct(new SiteContact());
        $this->service = $service;
    }
}
