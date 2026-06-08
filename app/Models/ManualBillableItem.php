<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualBillableItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service',
        'date',
        'total_amount',
        'note',
    ];

    protected $casts = [
        'service' => 'array',
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
