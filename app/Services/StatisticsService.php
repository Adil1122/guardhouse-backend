<?php

namespace App\Services;

use App\Models\User;
use App\Models\Site;
use App\Models\ShiftTimeclockLog;
use App\Models\ShiftAlert;
use App\Models\Timesheet;

class StatisticsService
{
    public function getSystemStatistics()
    {
        return [
            'totalUsers' => User::count(),
            'totalSites' => Site::count(),
            'staffOnDuty' => ShiftTimeclockLog::whereNull('clocked_out')->count(),
            'openAlerts' => ShiftAlert::whereNull('response_status')->count(),
        ];
    }
}
