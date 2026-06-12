<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\Site;
use App\Models\Checkin;
use App\Models\ShiftAlert;
use App\Models\ShiftTimeclockLog;
use App\Models\CheckCall;
use App\Models\ShiftNote;
use App\Models\Timesheet;

class WorkerController extends Controller
{
    // GET worker/sites
    public function sites(Request $request)
    {
        $userId = auth()->id();
        $siteIds = Shift::where('assigned_to', $userId)->pluck('site_id')->unique();
        $sites = Site::whereIn('id', $siteIds)->get();

        return response()->json($sites->map(function ($site) {
            return [
                'id' => $site->id,
                'name' => $site->name,
                'address' => $site->address,
                'status' => $site->status ?? 'Active',
            ];
        }), 200);
    }

    // GET worker/offered-shifts
    public function offeredShifts(Request $request)
    {
        $shifts = Shift::where('assigned_to', auth()->id())
            ->where('status', 'offered')
            ->with(['site', 'site.customer.user'])
            ->orderBy('start_date', 'asc')
            ->get();

        return response()->json($shifts->map(function ($shift) {
            return [
                'id' => $shift->id,
                'site_name' => $shift->site->name ?? '',
                'date' => $shift->start_date,
                'time' => ($shift->start_time ?? '') . ' – ' . ($shift->end_time ?? ''),
                'hours' => $shift->start_time && $shift->end_time
                    ? $shift->start_time . '–' . $shift->end_time
                    : '',
                'pay_note' => $shift->payGroup->name ?? '',
                'status' => $shift->status,
            ];
        }), 200);
    }

    // POST worker/shifts/{id}/accept
    public function acceptShift(Request $request, $id)
    {
        $shift = Shift::where('id', $id)->where('assigned_to', auth()->id())->first();
        if (!$shift) {
            return response()->json(['message' => 'Shift not found'], 404);
        }
        $shift->update(['status' => 'scheduled']);
        return response()->json(['message' => 'Shift accepted'], 200);
    }

    // POST worker/shifts/{id}/decline
    public function declineShift(Request $request, $id)
    {
        $shift = Shift::where('id', $id)->where('assigned_to', auth()->id())->first();
        if (!$shift) {
            return response()->json(['message' => 'Shift not found'], 404);
        }
        $shift->update(['status' => 'declined']);
        return response()->json(['message' => 'Shift declined'], 200);
    }

    // GET worker/check-calls
    public function checkCalls(Request $request)
    {
        $calls = CheckCall::where('worker_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($calls->map(function ($call) {
            return [
                'id' => $call->id,
                'status' => $call->status,
                'scheduled_at' => $call->scheduled_at,
                'responded_at' => $call->responded_at,
            ];
        }), 200);
    }

    // POST worker/check-calls/{id}/respond
    public function respondToCheckCall(Request $request, $id)
    {
        $call = CheckCall::where('id', $id)->where('worker_id', auth()->id())->first();
        if (!$call) {
            return response()->json(['message' => 'Check call not found'], 404);
        }
        $call->update(['status' => 'responded', 'responded_at' => now()]);
        return response()->json(['message' => 'Check call responded'], 200);
    }

    // GET worker/alarms
    public function alarmHistory(Request $request)
    {
        $alerts = ShiftAlert::where('user_id', auth()->id())
            ->with('shift')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($alerts->map(function ($alert) {
            return [
                'id' => $alert->id,
                'type' => $alert->type,
                'status' => $alert->response_status ?? 'raised',
                'timestamp' => $alert->created_at,
                'shift_id' => $alert->shift_id,
            ];
        }), 200);
    }

    // POST worker/alarms
    public function raiseAlarm(Request $request)
    {
        $userId = auth()->id();
        $shift = Shift::where('assigned_to', $userId)->where('status', 'clocked-in')->first();
        $shiftId = $shift?->id ?? $request->input('shift_id');

        if (!$shiftId) {
            return response()->json(['message' => 'No active shift. Please provide shift_id.'], 422);
        }

        $alert = ShiftAlert::create([
            'shift_id' => $shiftId,
            'user_id' => $userId,
            'type' => $request->input('type', 'in-app-notification'),
            'response_status' => null,
        ]);

        return response()->json(['message' => 'Alarm raised', 'id' => $alert->id], 201);
    }

    // POST worker/clock-in
    public function clockIn(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $log = ShiftTimeclockLog::create([
            'shift_id' => $request->shift_id,
            'user_id' => auth()->id(),
            'type' => 'clock_in',
            'clocked_in' => now(),
        ]);

        return response()->json(['message' => 'Clocked in', 'id' => $log->id], 201);
    }

    // POST worker/clock-out
    public function clockOut(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $log = ShiftTimeclockLog::where('shift_id', $request->shift_id)
            ->where('user_id', auth()->id())
            ->whereNull('clocked_out')
            ->latest()
            ->first();

        if ($log) {
            $log->update(['clocked_out' => now()]);
        }

        return response()->json(['message' => 'Clocked out'], 200);
    }

    // POST worker/shift/start
    public function startShift(Request $request)
    {
        $request->validate(['shift_id' => 'required|exists:shifts,id']);
        $shift = Shift::where('id', $request->shift_id)->where('assigned_to', auth()->id())->first();
        if (!$shift) {
            return response()->json(['message' => 'Shift not found'], 404);
        }
        $shift->update(['status' => 'clocked-in']);
        return response()->json(['message' => 'Shift started'], 200);
    }

    // POST worker/shift/{id}/end
    public function endShift(Request $request, $id)
    {
        $shift = Shift::where('id', $id)->where('assigned_to', auth()->id())->first();
        if (!$shift) {
            return response()->json(['message' => 'Shift not found'], 404);
        }
        $shift->update(['status' => 'ended']);
        
        $latestCheckin = \App\Models\Checkin::where('shift_id', $id)
            ->where('user_id', auth()->id())
            ->latest('checked_in_at')
            ->first();
            
        if ($latestCheckin) {
            $latestCheckin->update([
                'checked_out_at' => now(),
            ]);
        }
        
        return response()->json(['message' => 'Shift ended'], 200);
    }

    // GET worker/recent-checkins
    public function recentCheckins(Request $request)
    {
        $checkins = Checkin::where('user_id', auth()->id())
            ->with('shift')
            ->orderBy('checked_in_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($checkins, 200);
    }

    // GET worker/tasks (upcoming/pending shifts)
    public function tasks(Request $request)
    {
        $shifts = Shift::where('assigned_to', auth()->id())
            ->whereIn('status', ['created', 'awaiting', 'missed-clock-in', 'missed-alert'])
            ->with('site')
            ->get();

        $validShifts = $shifts->filter(function ($shift) {
            $tz = $shift->timezone ?: 'UTC';
            try {
                $now = now()->setTimezone($tz);
            } catch (\Exception $e) {
                $now = now();
            }
            
            $currentDate = $now->format('Y-m-d');
            $currentTime = $now->format('H:i:s');
            
            // Extract just the YYYY-MM-DD part safely
            $startDate = substr((string)$shift->start_date, 0, 10);
            $endDate = $shift->end_date ? substr((string)$shift->end_date, 0, 10) : $startDate;
            
            if ($currentDate < $startDate || $currentDate > $endDate) {
                return false;
            }
            
            // Extract HH:MM:SS safely
            $startTime = substr((string)$shift->start_time, 0, 8);
            if (strlen($startTime) == 5) $startTime .= ':00';
            
            $endTime = $shift->end_time ? substr((string)$shift->end_time, 0, 8) : $startTime;
            if (strlen($endTime) == 5) $endTime .= ':00';
            
            if ($startTime <= $endTime) {
                return $currentTime >= $startTime && $currentTime <= $endTime;
            } else {
                return $currentTime >= $startTime || $currentTime <= $endTime;
            }
        });

        $validShifts = $validShifts->sortBy(function ($shift) {
            return $shift->start_date . ' ' . $shift->start_time;
        })->values();

        return response()->json($validShifts->map(function ($shift) {
            return [
                'id' => $shift->id,
                'site_id' => $shift->site_id,
                'site_name' => $shift->site->name ?? '',
                'site_address' => is_string($shift->site->address) 
                    ? $shift->site->address 
                    : ($shift->site->address['formatted_address'] ?? 'Address not available'),
                'geofence' => $shift->site->geofence,
                'date' => $shift->start_date,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'status' => $shift->status,
            ];
        }), 200);
    }

    // POST worker/tasks/{id}/status
    public function updateTaskStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string']);
        $shift = Shift::where('id', $id)->where('assigned_to', auth()->id())->first();
        if (!$shift) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        $shift->update(['status' => $request->status]);
        return response()->json(['message' => 'Status updated'], 200);
    }

    // GET worker/attendance
    public function attendance(Request $request)
    {
        $timesheets = Timesheet::where('employee_user_id', auth()->id())
            ->with(['shift', 'site'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($timesheets, 200);
    }

    // GET worker/activities
    public function activities(Request $request)
    {
        $userId = auth()->id();

        $checkins = Checkin::where('user_id', $userId)
            ->orderBy('checked_in_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($c) {
                return [
                    'type' => 'checkin',
                    'description' => 'Check-in recorded',
                    'timestamp' => $c->checked_in_at,
                ];
            });

        return response()->json($checkins, 200);
    }

    // GET worker/notifications
    public function notifications(Request $request)
    {
        $notifications = auth()->user()->notifications()->orderBy('created_at', 'desc')->get();
        return response()->json($notifications->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'data' => $n->data,
                'read' => !is_null($n->read_at),
                'created_at' => $n->created_at,
            ];
        }), 200);
    }

    // POST worker/notifications/{id}/read
    public function markNotificationRead(Request $request, $id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['message' => 'Marked as read'], 200);
    }

    // POST worker/notifications/read-all
    public function markAllNotificationsRead(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All marked as read'], 200);
    }

    // DELETE worker/notifications/{id}
    public function deleteNotification(Request $request, $id)
    {
        auth()->user()->notifications()->where('id', $id)->delete();
        return response()->json(['message' => 'Notification deleted'], 200);
    }

    // GET worker/reports
    public function reports(Request $request)
    {
        $userId = auth()->id();
        $shiftIds = Shift::where('assigned_to', $userId)->pluck('id');
        $notes = ShiftNote::whereIn('shift_id', $shiftIds)
            ->with('shift')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notes, 200);
    }

    // POST worker/reports
    public function submitReport(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'notes' => 'required|string',
            'type' => 'nullable|in:short_note,customer,internal,invoice,position',
        ]);

        $note = ShiftNote::create([
            'shift_id' => $request->shift_id,
            'type' => $request->input('type', 'short_note'),
            'notes' => $request->notes,
        ]);

        return response()->json(['message' => 'Report submitted', 'id' => $note->id], 201);
    }
}
