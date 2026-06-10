<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    protected $fillable = [
        'supervisor_id',
        'site_id',
        'location',
        'purpose',
        'notes',
        'started_at',
        'ended_at',
        'summary',
        'has_issues',
        'issues_description',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'has_issues' => 'boolean',
    ];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
