<?php

namespace App\Events;

use App\Models\ServiceGroup;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceGroupCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $serviceGroup;

    /**
     * Create a new event instance.
     */
    public function __construct(ServiceGroup $serviceGroup)
    {
        $this->serviceGroup = $serviceGroup;
    }
}
