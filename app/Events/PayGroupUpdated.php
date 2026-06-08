<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\PayGroup;

class PayGroupUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payGroup;
    public $validatedData;

    public function __construct(PayGroup $payGroup, array $validatedData)
    {
        $this->payGroup = $payGroup;
        $this->validatedData = $validatedData;
    }
}
