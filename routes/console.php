<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Shift;
use App\Models\ShiftTimeclockLog;
use App\Notifications\ShiftReminderNotification;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-end shifts whose scheduled end time has passed
Schedule::call(function () {
    $now = Carbon::now();

    $overdueShifts = Shift::whereIn('status', ['clocked-in', 'checking-welfare'])
        ->whereNotNull('start_date')
        ->whereNotNull('end_time')
        ->get()
        ->filter(function ($shift) use ($now) {
            $end = Carbon::parse($shift->start_date . ' ' . $shift->end_time);
            // If end time is earlier than start time, it crosses midnight
            $start = Carbon::parse($shift->start_date . ' ' . $shift->start_time);
            if ($end->lte($start)) {
                $end->addDay();
            }
            return $now->gte($end);
        });

    foreach ($overdueShifts as $shift) {
        $shift->update(['status' => 'clocked-out']);

        $log = ShiftTimeclockLog::where('shift_id', $shift->id)
            ->whereNotNull('clocked_in')
            ->whereNull('clocked_out')
            ->latest('clocked_in')
            ->first();

        if ($log) {
            $clockedIn = Carbon::parse($log->clocked_in);
            $duration  = (int) $clockedIn->diffInMinutes($now);
            $log->update([
                'clocked_out'   => $now,
                'work_duration' => $duration,
            ]);
        }

        \Illuminate\Support\Facades\Log::info("Auto-ended overdue shift #{$shift->id}");
    }
})->everyMinute()->name('auto-end-overdue-shifts')->withoutOverlapping();

// Send "shift starts soon" reminders to assigned workers (backup for the
// on-device local reminder; covers app-killed / iOS-background cases).
Schedule::call(function () {
    $lead = (int) config('shifts.reminder_lead_minutes', 30);
    $now = Carbon::now();

    Shift::whereNotNull('assigned_to')
        ->whereNull('reminder_sent_at')
        ->whereNotNull('start_date')
        ->whereNotNull('start_time')
        ->whereIn('status', ['offered', 'created', 'confirmed', 'scheduled', 'awaiting'])
        ->with(['assignedUser', 'site'])
        ->get()
        ->each(function ($shift) use ($lead, $now) {
            $tz = $shift->timezone ?: 'Europe/London';
            $start = Carbon::parse(
                Carbon::parse($shift->start_date)->format('Y-m-d') . ' ' . $shift->start_time,
                $tz
            );

            // Due when we're inside the lead window and the shift hasn't started yet.
            if ($now->lt($start->copy()->subMinutes($lead)) || $now->gte($start)) {
                return;
            }

            if ($shift->assignedUser) {
                $shift->assignedUser->notify(new ShiftReminderNotification($shift));
            }
            $shift->forceFill(['reminder_sent_at' => $now])->saveQuietly();
        });
})->everyFiveMinutes()->name('shift-start-reminders')->withoutOverlapping();
