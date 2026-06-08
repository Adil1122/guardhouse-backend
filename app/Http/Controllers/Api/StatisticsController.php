<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    protected $service;

    public function __construct(StatisticsService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json([
            'statistics' => $this->service->getSystemStatistics()
        ], 200);
    }
}
