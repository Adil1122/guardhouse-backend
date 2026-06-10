<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckCall extends Model
{
    protected $fillable = [
        'worker_id',
        'shift_id',
        'status',
        'scheduled_at',
        'responded_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
