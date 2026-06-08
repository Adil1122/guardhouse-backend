<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Shift;
use App\Models\User;
use App\Models\DigitalOccurrenceLog;
use DB;
use Auth;

class ShiftTimeclockLogService
{
    public function create(array $data)
    {
        try {
            $result = DB::transaction(function () use ($data) {

                $shift = Shift::findOrFail($data['shift_id']);

                if ($data['action'] === 'clock-in') {
                    $log = $shift->timeclockLogs()->create($data);
                    $shift->update(['status' => 'clocked-in']);
                } elseif ($data['action'] === 'clock-out') {
                    $log = $shift->timeclockLogs()->whereNull('clocked_out')->latest()->first();
                    if (!$log) {
                        throw new \Exception('No clock-in log found to clock out.');
                    }

                    $log->update([
                        'clocked_out' => $data['clocked_out'],
                        'work_duration' => $data['work_duration'] ?? $log->work_duration,
                        'location' => $data['location'] ?? $log->location,
                    ]);

                    $shift->update(['status' => 'clocked-out']);
                } else {
                    throw new \Exception('Invalid action');
                }

                DigitalOccurrenceLog::create([
                    'shift_id' => $shift->id,
                    'type' => str_replace('-', '_', $data['action']),
                    'created_by' => Auth::id(),
                ]);

                return $log;
            });

            return [
                'success' => true,
                'message' => 'Time clock log created successfully',
                'data' => $result
            ];

        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Failed to create time clock log',
                'error' => $th->getMessage()
            ];
        }
    }
    
    public function canClockInOut(User $user, Shift $shift, string $action): bool
    {
        if (!in_array($user->role, ['security-officer', 'supervisor', 'admin'])) {
            return false;
        }

        if ($shift->assigned_to !== $user->id && $user->role !== 'admin') {
            return false;
        }
        
        if (!$shift->timesheet) {
            return false;
        }

        $tz = $shift->timezone ?? 'UTC';
        $timesheet = $shift->timesheet;
        $shiftStart = Carbon::parse($timesheet->start_date . ' ' . $timesheet->start_time, $tz);
        $shiftEnd = Carbon::parse($timesheet->end_date . ' ' . $timesheet->end_time, $tz);
        $now = Carbon::now($tz);

        if ($action === 'clock-in') {

            if (!in_array($shift->status, ['confirmed', 'clocked-out'])) {
                return false;
            }

            $allowedStartTime = $shiftStart->copy()->subMinutes(5);

            if ($now->lt($allowedStartTime)) {
                return false;
            }

            if ($now->gt($shiftEnd)) {
                return false;
            }

            return true;
        }

        if ($action === 'clock-out') {

            if (!in_array($shift->status, ['clocked-in', 'checking-welfare', 'missed-alert'])) {
                return false;
            }

            $allowedEndTime = $shiftEnd->copy()->addMinutes(5);

            if ($now->lt($shiftStart)) {
                return false;
            }

            if ($now->gt($allowedEndTime)) {
                return false;
            }

            return true;
        }

        return false;
    }
    
    public function resolveClockInOutDateTime(Shift $shift): string
    {
        return Carbon::now($shift->timesheet->timezone)->format('Y-m-d H:i:s');
    }

    public function calculateWorkDuration(Shift $shift, string $clockedOut): float
    {
        $clockedIn = Carbon::parse($shift->timeclockLogs()->whereNull('clocked_out')->latest()->first()->clocked_in, $shift->timesheet->timezone);
        $clockedOut = Carbon::parse($clockedOut, $shift->timesheet->timezone);
        return $clockedIn->diffInMinutes($clockedOut);
    }
}