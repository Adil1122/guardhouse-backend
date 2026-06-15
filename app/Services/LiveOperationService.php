<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Checkin;
use App\Models\ShiftAlert;
use Illuminate\Support\Facades\Auth;

class LiveOperationService
{
    public function list()
    {
        $activeShifts = Shift::whereIn('status', [
                'clocked-in', 'checking-welfare', 'scheduled', 'confirmed',
                'missed-alert', 'missed-clock-in', 'clocked-out-offsite',
            ])
            ->with(['site', 'assignedUser'])
            ->orderBy('start_date', 'asc')
            ->get();

        $recentAlerts = ShiftAlert::whereNull('response_status')
            ->with(['user', 'shift.site'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $recentCheckins = Checkin::with(['user', 'shift.site'])
            ->orderBy('checked_in_at', 'desc')
            ->limit(20)
            ->get();

        return [
            'active_shifts' => $activeShifts->map(function ($shift) {
                return [
                    'id' => $shift->id,
                    'site_name' => $shift->site?->name ?? 'Unknown Site',
                    'worker_name' => trim(($shift->assignedUser?->first_name ?? '') . ' ' . ($shift->assignedUser?->last_name ?? '')) ?: ($shift->assignedUser?->email ?? 'Unassigned'),
                    'status' => $shift->status,
                    'start_time' => $shift->start_time,
                    'end_time' => $shift->end_time,
                    'date' => $shift->start_date,
                ];
            }),
            'recent_alerts' => $recentAlerts->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'worker_name' => trim(($alert->user?->first_name ?? '') . ' ' . ($alert->user?->last_name ?? '')) ?: ($alert->user?->email ?? 'Unknown'),
                    'site_name' => $alert->shift?->site?->name ?? 'Unknown Site',
                    'timestamp' => $alert->created_at?->toISOString(),
                ];
            }),
            'recent_checkins' => $recentCheckins->map(function ($checkin) {
                return [
                    'id' => $checkin->id,
                    'worker_name' => trim(($checkin->user?->first_name ?? '') . ' ' . ($checkin->user?->last_name ?? '')) ?: ($checkin->user?->email ?? 'Unknown'),
                    'site_name' => $checkin->shift?->site?->name ?? 'Unknown Site',
                    'location' => $checkin->location_description,
                    'timestamp' => $checkin->checked_in_at?->toISOString(),
                    'inside_geofence' => $checkin->inside_geofence,
                ];
            }),
            'summary' => [
                'active_shifts_count' => $activeShifts->count(),
                'unresponded_alerts' => $recentAlerts->count(),
                'checkins_today' => Checkin::whereDate('checked_in_at', today())->count(),
            ],
        ];
    }
}
