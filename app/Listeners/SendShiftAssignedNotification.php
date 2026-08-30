<?php

namespace App\Listeners;

use App\Events\ShiftCreated;
use App\Notifications\ShiftAssignedNotification;

class SendShiftAssignedNotification
{
    public function handle(ShiftCreated $event): void
    {
        $shift = $event->shift;

        if ($shift->assigned_to && $shift->assignedUser) {
            $shift->loadMissing('site');
            $shift->assignedUser->notify(new ShiftAssignedNotification($shift));
        }
    }
}
