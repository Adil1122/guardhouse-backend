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

    // GET worker/assigned-site
    public function assignedSite(Request $request)
    {
        $userId = auth()->id();

        // Priority 1: active (clocked-in) shift
        $shift = Shift::where('assigned_to', $userId)
            ->whereIn('status', ['clocked-in', 'checking-welfare'])
            ->with('site')
            ->first();
        $shiftStatus = 'active';

        // Priority 2: next upcoming scheduled shift today or future
        if (!$shift) {
            $shift = Shift::where('assigned_to', $userId)
                ->whereIn('status', ['scheduled', 'confirmed', 'created', 'offered'])
                ->where('start_date', '>=', now()->toDateString())
                ->orderBy('start_date')
                ->orderBy('start_time')
                ->with('site')
                ->first();
            $shiftStatus = 'upcoming';
        }

        if (!$shift || !$shift->site) {
            return response()->json(['site' => null], 200);
        }

        $site    = $shift->site;
        $rawAddr = $site->address;
        $address = is_array($rawAddr)
            ? ($rawAddr['formatted_address'] ?? $rawAddr['street'] ?? 'Address not available')
            : (is_string($rawAddr) && !empty($rawAddr) ? $rawAddr : 'Address not available');

        $startStr = $shift->start_time ? substr($shift->start_time, 0, 5) : '';
        $endStr   = $shift->end_time   ? substr($shift->end_time, 0, 5)   : '';
        $dateStr  = $shift->start_date
            ? \Carbon\Carbon::parse($shift->start_date)->format('M j, D')
            : '';

        return response()->json([
            'site' => [
                'id'         => $site->id,
                'name'       => $site->name ?? 'Unknown Site',
                'address'    => $address,
                'shift_id'   => $shift->id,
                'shift_date' => $dateStr,
                'shift_time' => $startStr && $endStr ? "$startStr – $endStr" : '',
                'status'     => $shiftStatus,
            ],
        ], 200);
    }

    // GET worker/my-shifts
    public function myShifts(Request $request)
    {
        $shifts = Shift::where('assigned_to', auth()->id())
            ->whereNotIn('status', ['rejected'])
            ->with(['site', 'payGroup'])
            ->orderBy('start_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        // Pin the currently active (clocked-in / checking-welfare) shift to the
        // top regardless of its date, then keep the latest-first ordering for
        // everything else.
        $shifts = $shifts->sortByDesc(function ($shift) {
            $isActive = in_array($shift->status, ['clocked-in', 'checking-welfare']) ? '1' : '0';
            return $isActive . '_' . $shift->start_date . ' ' . $shift->start_time;
        })->values();

        return response()->json([
            'shifts' => $shifts->map(function ($shift) {
                $startStr = $shift->start_time ? substr($shift->start_time, 0, 5) : '';
                $endStr   = $shift->end_time   ? substr($shift->end_time, 0, 5)   : '';

                $hoursStr = '';
                if ($shift->start_time && $shift->end_time) {
                    $s = \Carbon\Carbon::createFromFormat('H:i:s', $shift->start_time);
                    $e = \Carbon\Carbon::createFromFormat('H:i:s', $shift->end_time);
                    if ($e->lte($s)) $e->addDay();
                    $mins = $s->diffInMinutes($e);
                    $hoursStr = $mins % 60 === 0
                        ? ($mins / 60) . ' hrs'
                        : round($mins / 60, 1) . ' hrs';
                }

                $dateStr = $shift->start_date
                    ? \Carbon\Carbon::parse($shift->start_date)->format('M j, D')
                    : '';

                $normalizedStatus = match ($shift->status) {
                    'clocked-in', 'checking-welfare'  => 'active',
                    'clocked-out', 'clocked-out-offsite' => (function () use ($shift) {
                        $log = \App\Models\ShiftTimeclockLog::where('shift_id', $shift->id)
                            ->whereNotNull('clocked_out')
                            ->latest('clocked_out')
                            ->first();
                        if ($log && $shift->end_time) {
                            $scheduledEnd = \Carbon\Carbon::parse($shift->start_date . ' ' . $shift->end_time);
                            $scheduledStart = $shift->start_time
                                ? \Carbon\Carbon::parse($shift->start_date . ' ' . $shift->start_time)
                                : null;
                            if ($scheduledStart && !$scheduledEnd->isAfter($scheduledStart)) {
                                $scheduledEnd->addDay();
                            }
                            $clockedOut = \Carbon\Carbon::parse($log->clocked_out);
                            if ($clockedOut->lt($scheduledEnd)) {
                                return 'ended_early';
                            }
                        }
                        return 'completed';
                    })(),
                    'missed-alert', 'missed-clock-in' => 'missed',
                    'offered'                         => 'offered',
                    default                           => 'upcoming',
                };

                return [
                    'id'         => $shift->id,
                    'site_name'  => $shift->site?->name ?? 'Unknown Site',
                    'date'       => $dateStr,
                    'start_time' => $startStr,
                    'end_time'   => $endStr,
                    'time'       => $startStr && $endStr ? "$startStr – $endStr" : '',
                    'hours'      => $hoursStr,
                    'pay_note'   => $shift->payGroup?->name ?? 'Standard rate',
                    'status'     => $normalizedStatus,
                    'start_at'   => ($shift->start_date && $shift->start_time)
                        ? \Carbon\Carbon::parse(
                            \Carbon\Carbon::parse($shift->start_date)->format('Y-m-d') . ' ' . $shift->start_time,
                            $shift->timezone ?: 'Europe/London'
                        )->toIso8601String()
                        : null,
                ];
            }),
        ], 200);
    }

    // GET worker/offered-shifts
    public function offeredShifts(Request $request)
    {
        // Include 'created' (admin-assigned but not yet responded to) and 'offered' statuses
        $shifts = Shift::where('assigned_to', auth()->id())
            ->whereIn('status', ['offered', 'created'])
            ->with(['site', 'payGroup'])
            ->orderBy('start_date', 'asc')
            ->get();

        return response()->json([
            'shifts' => $shifts->map(function ($shift) {
                $startStr = $shift->start_time ? substr($shift->start_time, 0, 5) : '';
                $endStr   = $shift->end_time   ? substr($shift->end_time, 0, 5)   : '';

                $hoursStr = '';
                if ($shift->start_time && $shift->end_time) {
                    $s = \Carbon\Carbon::createFromFormat('H:i:s', $shift->start_time);
                    $e = \Carbon\Carbon::createFromFormat('H:i:s', $shift->end_time);
                    if ($e->lte($s)) $e->addDay();
                    $mins = $s->diffInMinutes($e);
                    $hoursStr = $mins % 60 === 0
                        ? ($mins / 60) . ' hrs'
                        : round($mins / 60, 1) . ' hrs';
                }

                $dateStr = $shift->start_date
                    ? \Carbon\Carbon::parse($shift->start_date)->format('M j, D')
                    : '';

                return [
                    'id'        => $shift->id,
                    'site_name' => $shift->site?->name ?? '',
                    'date'      => $dateStr,
                    'time'      => $startStr && $endStr ? "$startStr – $endStr" : '',
                    'hours'     => $hoursStr,
                    'pay_note'  => $shift->payGroup?->name ?? 'Standard rate',
                    'status'    => $shift->status,
                    'start_at'  => ($shift->start_date && $shift->start_time)
                        ? \Carbon\Carbon::parse(
                            \Carbon\Carbon::parse($shift->start_date)->format('Y-m-d') . ' ' . $shift->start_time,
                            $shift->timezone ?: 'Europe/London'
                        )->toIso8601String()
                        : null,
                ];
            }),
        ], 200);
    }

    // POST worker/shifts/{id}/accept
    public function acceptShift(Request $request, $id)
    {
        $shift = Shift::where('id', $id)->where('assigned_to', auth()->id())->first();
        if (!$shift) {
            return response()->json(['message' => 'Shift not found'], 404);
        }
        $shift->update(['status' => 'confirmed']);
        return response()->json(['message' => 'Shift accepted'], 200);
    }

    // POST worker/shifts/{id}/decline
    public function declineShift(Request $request, $id)
    {
        $shift = Shift::where('id', $id)->where('assigned_to', auth()->id())->first();
        if (!$shift) {
            return response()->json(['message' => 'Shift not found'], 404);
        }
        $shift->update(['status' => 'rejected']);
        return response()->json(['message' => 'Shift declined'], 200);
    }

    // POST worker/check-calls — auto-create hourly check call
    public function createAutoCheckCall(Request $request)
    {
        $workerId = auth()->id();

        // Auto-expire any pending check calls older than 15 minutes → missed
        CheckCall::where('worker_id', $workerId)
            ->where('status', 'pending')
            ->where('scheduled_at', '<', now()->subMinutes(15))
            ->update(['status' => 'missed']);

        // Idempotent: skip if a pending one already exists (created in this window)
        $existing = CheckCall::where('worker_id', $workerId)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Pending check call already exists', 'id' => $existing->id], 200);
        }

        $call = CheckCall::create([
            'worker_id'    => $workerId,
            'status'       => 'pending',
            'scheduled_at' => now(),
        ]);

        return response()->json(['message' => 'Check call created', 'id' => $call->id], 201);
    }

    // GET worker/check-calls
    public function checkCalls(Request $request)
    {
        $calls = CheckCall::where('worker_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'check_calls' => $calls->map(function ($call) {
                $displayTime = $call->scheduled_at ?? $call->created_at;
                return [
                    'id' => $call->id,
                    'status' => $call->status,
                    'timestamp' => $displayTime ? $displayTime->format('M j, Y H:i') : '',
                    'scheduled_at' => $call->scheduled_at,
                    'responded_at' => $call->responded_at,
                ];
            }),
        ], 200);
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

    // GET worker/alarms — returns all alarms so workers are notified of admin-raised alarms
    public function alarmHistory(Request $request)
    {
        $alerts = ShiftAlert::with(['user', 'shift'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['alarms' => $alerts->map(function ($alert) {
            $user = $alert->user;
            return [
                'id'          => $alert->id,
                'type'        => $alert->type ?? 'Emergency',
                'location'    => $alert->location ?? '',
                'description' => $alert->description ?? '',
                'status'      => $alert->response_status ?? 'raised',
                'timestamp'   => $alert->created_at?->toISOString(),
                'shift_id'    => $alert->shift_id,
                'user_name'   => $user ? trim($user->first_name . ' ' . $user->last_name) : '',
            ];
        })], 200);
    }

    // POST worker/alarms
    public function raiseAlarm(Request $request)
    {
        $userId = auth()->id();
        $shift = Shift::where('assigned_to', $userId)->whereIn('status', ['clocked-in', 'checking-welfare'])->first();
        $shiftId = $shift?->id ?? $request->input('shift_id');

        if (!$shiftId) {
            return response()->json(['message' => 'No active shift. Please provide shift_id.'], 422);
        }

        $alert = ShiftAlert::create([
            'shift_id'    => $shiftId,
            'user_id'     => $userId,
            'type'        => $request->input('type', 'Emergency'),
            'location'    => $request->input('location'),
            'description' => $request->input('description'),
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

        $active = Shift::where('assigned_to', auth()->id())
            ->whereIn('status', ['clocked-in', 'checking-welfare'])
            ->where('id', '!=', $request->shift_id)
            ->with('site')
            ->first();

        if ($active) {
            return response()->json([
                'message' => 'You are already on duty at ' . ($active->site?->name ?? 'another site') . '. Please complete that shift before starting a new one.',
                'active_shift_id' => $active->id,
                'active_site' => $active->site?->name,
            ], 409);
        }

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

        if ($shift->start_date && $shift->start_time) {
            $tz = $shift->timezone ?: 'Europe/London';
            try {
                // For multi-day shifts (start_date..end_date), anchor the
                // time-of-day check to today if we're within the shift's date
                // range, otherwise to its first scheduled day — never anchor
                // to a date that's already behind us once we're inside the range.
                $startDateStr = substr((string) $shift->start_date, 0, 10);
                $endDateStr = $shift->end_date ? substr((string) $shift->end_date, 0, 10) : $startDateStr;
                $todayStr = now()->setTimezone($tz)->format('Y-m-d');

                if ($todayStr > $endDateStr) {
                    return response()->json([
                        'message' => "This shift's scheduled window has already ended. Please contact your supervisor.",
                    ], 422);
                }

                $anchorDate = $todayStr >= $startDateStr ? $todayStr : $startDateStr;

                $scheduledStart = \Carbon\Carbon::parse($anchorDate . ' ' . $shift->start_time, $tz);
                $earliestStart = $scheduledStart->copy()->subMinutes(15);
                $nowInTz = now()->setTimezone($tz);

                if ($nowInTz->lt($earliestStart)) {
                    return response()->json([
                        'message' => 'You can start this shift no earlier than 15 minutes before its scheduled start time (' . $scheduledStart->format('M d, Y h:i A') . ').',
                    ], 422);
                }
            } catch (\Exception $e) {
                // If the shift's date/time can't be parsed, don't block the start.
            }
        }

        $active = Shift::where('assigned_to', auth()->id())
            ->whereIn('status', ['clocked-in', 'checking-welfare'])
            ->where('id', '!=', $shift->id)
            ->with('site')
            ->first();

        if ($active) {
            return response()->json([
                'message' => 'You are already on duty at ' . ($active->site?->name ?? 'another site') . '. Please complete that shift before starting a new one.',
                'active_shift_id' => $active->id,
                'active_site' => $active->site?->name,
            ], 409);
        }

        $shift->update(['status' => 'clocked-in']);

        ShiftTimeclockLog::create([
            'shift_id' => $shift->id,
            'clocked_in' => now(),
        ]);

        return response()->json(['message' => 'Shift started'], 200);
    }

    // POST worker/shift/{id}/end
    public function endShift(Request $request, $id)
    {
        $shift = Shift::where('id', $id)->where('assigned_to', auth()->id())->first();
        if (!$shift) {
            return response()->json(['message' => 'Shift not found'], 404);
        }

        // Record actual clock-out and work_duration in the timeclock log
        $log = ShiftTimeclockLog::where('shift_id', $id)
            ->whereNotNull('clocked_in')
            ->whereNull('clocked_out')
            ->latest('clocked_in')
            ->first();

        if ($shift->end_time) {
            $tz = $shift->timezone ?: 'Europe/London';
            try {
                // Anchor to the day the worker actually clocked in, not the
                // shift's (possibly multi-day-range) start_date — otherwise a
                // worker who joins partway through a date range would have
                // the end-time check computed against an already-past day.
                $anchorDate = $log
                    ? \Carbon\Carbon::parse($log->clocked_in)->setTimezone($tz)->format('Y-m-d')
                    : substr((string) $shift->start_date, 0, 10);

                $scheduledEnd = \Carbon\Carbon::parse($anchorDate . ' ' . $shift->end_time, $tz);
                $scheduledStart = $shift->start_time
                    ? \Carbon\Carbon::parse($anchorDate . ' ' . $shift->start_time, $tz)
                    : null;
                if ($scheduledStart && !$scheduledEnd->isAfter($scheduledStart)) {
                    $scheduledEnd->addDay();
                }
                $nowInTz = now()->setTimezone($tz);

                if ($nowInTz->lt($scheduledEnd)) {
                    return response()->json([
                        'message' => 'You cannot end this shift before its scheduled end time (' . $scheduledEnd->format('M d, Y h:i A') . ').',
                    ], 422);
                }
            } catch (\Exception $e) {
                // If the shift's date/time can't be parsed, don't block ending.
            }
        }

        $now = now();
        $shift->update(['status' => 'clocked-out']);

        if ($log) {
            $clockedIn = \Carbon\Carbon::parse($log->clocked_in);
            $duration = (int) $clockedIn->diffInMinutes($now);
            $log->update([
                'clocked_out'   => $now,
                'work_duration' => $duration,
            ]);
        }

        return response()->json([
            'message'       => 'Shift ended',
            'clocked_out'   => $now->toISOString(),
            'work_duration' => $log ? $log->work_duration : 0,
        ], 200);
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
            ->whereIn('status', ['created', 'offered', 'scheduled', 'confirmed', 'awaiting', 'missed-clock-in', 'missed-alert'])
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
            // For multi-day shifts (start_date..end_date), the relevant calendar
            // day for "today's" occurrence is today (in the shift's timezone),
            // not the shift's original start_date — otherwise a worker joining
            // partway through the date range would have all start/end time math
            // anchored to a day that's already passed.
            $tz = $shift->timezone ?: 'Europe/London';
            try {
                $occurrenceDate = now()->setTimezone($tz)->format('Y-m-d');
            } catch (\Exception $e) {
                $occurrenceDate = $shift->start_date;
            }

            return [
                'id' => $shift->id,
                'site_id' => $shift->site_id,
                'site_name' => $shift->site->name ?? '',
                'site_address' => is_string($shift->site->address)
                    ? $shift->site->address
                    : ($shift->site->address['formatted_address'] ?? 'Address not available'),
                'geofence' => $shift->site->geofence,
                'date' => $occurrenceDate,
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
        return response()->json(['notifications' => $notifications->map(function ($n) {
            $data = is_array($n->data) ? $n->data : [];
            // Derive a short type slug from the PHP class name for the frontend icon
            $typeParts = explode('\\', $n->type ?? '');
            $shortType = strtolower(end($typeParts));
            $isAlert = str_contains($shortType, 'alert') || str_contains($shortType, 'alarm') || str_contains($shortType, 'welfare');
            $isShift = str_contains($shortType, 'shift') || (is_array($n->data) && ($n->data['type'] ?? null) === 'shift');
            return [
                'id'         => $n->id,
                'type'       => $isAlert ? 'alert' : ($isShift ? 'shift' : 'info'),
                'title'      => $data['title'] ?? ($isAlert ? 'Alert' : 'Notification'),
                'message'    => $data['message'] ?? $data['body'] ?? $data['content'] ?? 'You have a new notification',
                'read'       => !is_null($n->read_at),
                'created_at' => $n->created_at?->toISOString(),
            ];
        })], 200);
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
