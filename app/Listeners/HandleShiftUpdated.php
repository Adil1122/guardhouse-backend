<?php

namespace App\Listeners;

use App\Events\ShiftUpdated;
use App\Models\User;
use App\Notifications\ShiftAssignedNotification;
use App\Notifications\ShiftCancelledNotification;
use App\Notifications\ShiftChangedNotification;

class HandleShiftUpdated
{
    /** Fields whose change is worth telling an already-assigned worker about. */
    private const DETAIL_FIELDS = ['site_id', 'start_date', 'end_date', 'start_time', 'end_time'];

    public function handle(ShiftUpdated $event): void
    {
        $shift = $event->shift;
        $old = $event->oldData;

        $oldAssignee = $old['assigned_to'] ?? null;
        $newAssignee = $shift->assigned_to;

        // Re-arm the start reminder if the start moved.
        $startMoved = $this->changed($shift, $old, ['start_date', 'start_time']);
        if ($startMoved && $shift->reminder_sent_at !== null) {
            $shift->forceFill(['reminder_sent_at' => null])->saveQuietly();
        }

        if ((string) $oldAssignee !== (string) $newAssignee) {
            // Worker removed from / swapped off this shift.
            if ($oldAssignee && ($prev = User::find($oldAssignee))) {
                $prev->notify(new ShiftCancelledNotification($shift));
            }
            // New worker assigned.
            if ($newAssignee && $shift->assignedUser) {
                $shift->loadMissing('site');
                $shift->assignedUser->notify(new ShiftAssignedNotification($shift));
            }
            return;
        }

        // Same assignee — notify only when a meaningful detail changed.
        if ($newAssignee && $shift->assignedUser && $this->changed($shift, $old, self::DETAIL_FIELDS)) {
            $shift->loadMissing('site');
            $shift->assignedUser->notify(new ShiftChangedNotification($shift));
        }
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<int, string>  $fields
     */
    private function changed($shift, array $old, array $fields): bool
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $old) && (string) $old[$field] !== (string) $shift->{$field}) {
                return true;
            }
        }

        return false;
    }
}
