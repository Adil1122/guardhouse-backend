<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\ServiceGroup;

class ServiceGroupUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $serviceGroup;
    public $validatedData;

    public function __construct(ServiceGroup $serviceGroup, array $validatedData)
    {
        $this->serviceGroup = $serviceGroup;
        $this->validatedData = $validatedData;
    }
}
