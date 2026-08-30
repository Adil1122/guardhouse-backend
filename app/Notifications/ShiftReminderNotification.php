<?php

namespace App\Notifications;

class ShiftReminderNotification extends ShiftNotification
{
    protected function title(): string
    {
        return 'Shift starts soon';
    }

    protected function body(): string
    {
        $time = $this->shift->start_time ? substr($this->shift->start_time, 0, 5) : null;
        $at = $time ? " at {$time}" : ' soon';

        return "Your shift at {$this->siteName()} starts{$at}.";
    }
}
