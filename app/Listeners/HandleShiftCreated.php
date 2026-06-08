<?php

namespace App\Listeners;

use App\Events\ShiftCreated;
use App\Models\Timesheet;

class HandleShiftCreated
{
    public function __construct()
    {

    }

    public function handle(ShiftCreated $event)
    {
        $shift = $event->shift;

        Timesheet::create([
            'shift_id' => $shift->id,
            'entry_type' => 'work',
            'site_id' => $shift->site_id,
            'employee_user_id' => $shift->assigned_to ?? null,
            'start_date' => $shift->start_date,
            'end_date' => $shift->end_date,
            'timezone' => $shift->timezone,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'break_duration' => $shift->break_duration,
            'status' => 'drafted',
            'notes' => null,
        ]);
    }
}
