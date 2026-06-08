<?php

namespace App\Listeners;

use App\Events\ServiceGroupCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleServiceGroupCreated
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ServiceGroupCreated $event): void
    {
        $rates = request()->input('rates', []);

        if (is_array($rates) && count($rates) > 0) {
            foreach ($rates as $rate) {
                $event->serviceGroup->rates()->create([
                    'days' => $rate['days'],
                    'from_time' => $rate['from_time'],
                    'to_time' => $rate['to_time'],
                    'rate' => $rate['rate'],
                ]);
            }
        }
    }
}
