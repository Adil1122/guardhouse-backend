<?php

namespace App\Notifications;

class ShiftChangedNotification extends ShiftNotification
{
    protected function title(): string
    {
        return 'Shift updated';
    }

    protected function body(): string
    {
        $when = $this->whenLabel();
        $when = $when !== '' ? " It's now {$when}." : '';

        return "Your shift at {$this->siteName()} has been updated.{$when}";
    }
}
