<?php

namespace App\Notifications;

class ShiftAssignedNotification extends ShiftNotification
{
    protected function title(): string
    {
        return 'New shift assigned';
    }

    protected function body(): string
    {
        $when = $this->whenLabel();
        $when = $when !== '' ? " on {$when}" : '';

        return "You've been assigned to {$this->siteName()}{$when}.";
    }
}
