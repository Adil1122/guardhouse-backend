<?php

return [

    /*
    | How many minutes before a shift's start time the "shift starts soon"
    | reminder notification should be sent by the server-side scheduler.
    | The on-device local reminder (Flutter) uses the same lead time.
    */
    'reminder_lead_minutes' => (int) env('SHIFT_REMINDER_LEAD_MINUTES', 30),

];
