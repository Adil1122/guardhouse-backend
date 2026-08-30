<?php

namespace App\Listeners;

use App\Events\ShiftDeleted;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\ShiftCancelledNotification;

class HandleShiftDeleted
{
    public function handle(ShiftDeleted $event): void
    {
        $data = $event->data;

        $assignee = $data['assigned_to'] ?? null;
        if (! $assignee) {
            return;
        }

        $user = User::find($assignee);
        if (! $user) {
            return;
        }

        // Rebuild a lightweight, unsaved Shift so the notification can format
        // the site/date/time. `site` is loaded by id if available.
        $shift = (new Shift())->forceFill($data);
        if (! empty($data['site_id'])) {
            $shift->setRelation('site', \App\Models\Site::find($data['site_id']));
        }

        $user->notify(new ShiftCancelledNotification($shift));
    }
}
