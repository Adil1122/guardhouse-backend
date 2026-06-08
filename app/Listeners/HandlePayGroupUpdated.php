<?php

namespace App\Listeners;

use App\Events\PayGroupUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandlePayGroupUpdated
{
    public function handle(PayGroupUpdated $event): void
    {
        $payGroup = $event->payGroup;
        $rates = $event->validatedData['rates'] ?? [];

        if (is_array($rates)) {
            $payGroup->rates()->delete();

            foreach ($rates as $index => $rate) {
                $payGroup->rates()->create([
                    'days' => $rate['days'],
                    'from_time' => $rate['from_time'],
                    'to_time' => $rate['to_time'],
                    'rate' => $rate['rate'],
                ]);
            }
        }
    }
}
