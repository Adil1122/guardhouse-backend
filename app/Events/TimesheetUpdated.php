<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Timesheet;

class TimesheetUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $timesheet;
    public $validatedData;
    public $oldData;

    /**
     * Create a new event instance.
     */
    public function __construct(Timesheet $timesheet, array $validatedData, array $oldData)
    {
        $this->timesheet = $timesheet;
        $this->validatedData = $validatedData;
        $this->oldData = $oldData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
