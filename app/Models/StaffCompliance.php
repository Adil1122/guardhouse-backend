<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffCompliance extends Model
{
    use HasFactory;

    protected $fillable = ['staff_profile_id', 'compliance_id', 'start_date', 'end_date', 'files'];

    public function staffProfile()
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function compliance()
    {
        return $this->belongsTo(OrganizationCompliance::class, 'compliance_id');
    }

    protected $casts = ['files' => 'array' ];
}
