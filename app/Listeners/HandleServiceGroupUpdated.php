<?php

namespace App\Listeners;

use App\Events\ServiceGroupUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleServiceGroupUpdated
{
    public function handle(ServiceGroupUpdated $event): void
    {
        $serviceGroup = $event->serviceGroup;
        $rates = $event->validatedData['rates'] ?? [];

        if (is_array($rates)) {
            $serviceGroup->rates()->delete();

            foreach ($rates as $index => $rate) {
                $serviceGroup->rates()->create([
                    'days' => $rate['days'],
                    'from_time' => $rate['from_time'],
                    'to_time' => $rate['to_time'],
                    'rate' => $rate['rate'],
                ]);
            }
        }
    }
}
