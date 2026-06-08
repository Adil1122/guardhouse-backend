<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayRate extends Model
{
    protected $fillable = [
        'pay_group_id',
        'days',
        'from_time',
        'to_time',
        'rate',
    ];

    protected $casts = [
        'days' => 'array',
        'rate' => 'decimal:2',
    ];

    public function payGroup()
    {
        return $this->belongsTo(PayGroup::class);
    }
}
