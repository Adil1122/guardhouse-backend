<?php

namespace App\Listeners;

use App\Events\PayGroupCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandlePayGroupCreated
{
    public function handle(PayGroupCreated $event): void
    {
        $rates = request()->input('rates', []);

        if (is_array($rates) && count($rates) > 0) {
            foreach ($rates as $rate) {
                $event->payGroup->rates()->create([
                    'days' => $rate['days'],
                    'from_time' => $rate['from_time'],
                    'to_time' => $rate['to_time'],
                    'rate' => $rate['rate'],
                ]);
            }
        }
    }
}
