<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Services\DigitalOccurrenceLogService;
use App\Models\DigitalOccurrenceLog;

class DigitalOccurrenceLogController extends BaseController
{
    protected $service;
    protected $filterKey = null;

    public function __construct(DigitalOccurrenceLogService $service)
    {
        parent::__construct(new DigitalOccurrenceLog());
        $this->service = $service;
    }
}
