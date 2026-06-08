<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffPrivilege extends Model
{
    use HasFactory;

    protected $fillable = ['staff_profile_id', 'privileges'];

    protected $casts = [
        'privileges' => 'array'
    ];

    public function staffProfile()
    {
        return $this->belongsTo(StaffProfile::class);
    }
}
