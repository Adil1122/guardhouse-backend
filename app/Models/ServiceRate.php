<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRate extends Model
{
    protected $fillable = [
        'service_group_id',
        'days',
        'from_time',
        'to_time',
        'rate',
    ];

    protected $casts = [
        'days' => 'array',
        'rate' => 'decimal:2',
    ];

    public function serviceGroup()
    {
        return $this->belongsTo(ServiceGroup::class);
    }
}
