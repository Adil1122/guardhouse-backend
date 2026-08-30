<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftDeleted
{
    use Dispatchable, SerializesModels;

    /** @var array<string, mixed> */
    public $data;

    /**
     * @param  array<string, mixed>  $data  The shift attributes as they were before deletion.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
