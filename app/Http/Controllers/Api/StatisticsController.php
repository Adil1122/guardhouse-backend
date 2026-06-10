<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use App\Models\Checkin;
use App\Models\Shift;
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

    public function activities(Request $request)
    {
        $checkins = Checkin::with(['user', 'shift'])
            ->orderBy('checked_in_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($c) {
                return [
                    'type' => 'checkin',
                    'description' => ($c->user?->name ?? 'Worker') . ' checked in',
                    'timestamp' => $c->checked_in_at,
                    'user_id' => $c->user_id,
                ];
            });

        return response()->json($checkins, 200);
    }

    public function systemLogs(Request $request)
    {
        return response()->json([], 200);
    }
}
