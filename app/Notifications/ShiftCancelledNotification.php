<?php

namespace App\Notifications;

class ShiftCancelledNotification extends ShiftNotification
{
    protected function title(): string
    {
        return 'Shift cancelled';
    }

    protected function body(): string
    {
        $when = $this->whenLabel();
        $when = $when !== '' ? " on {$when}" : '';

        return "Your shift at {$this->siteName()}{$when} has been cancelled.";
    }
}
