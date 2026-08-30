<?php

namespace App\Events;

use App\Models\Shift;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftUpdated
{
    use Dispatchable, SerializesModels;

    public $shift;
    public $changes;
    public $oldData;

    /**
     * @param  array<string, mixed>  $changes  The validated attributes that were written.
     * @param  array<string, mixed>  $oldData  The shift attributes before the update.
     */
    public function __construct(Shift $shift, array $changes = [], array $oldData = [])
    {
        $this->shift = $shift;
        $this->changes = $changes;
        $this->oldData = $oldData;
    }
}
