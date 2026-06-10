<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupervisorReport extends Model
{
    protected $fillable = [
        'supervisor_id',
        'site_id',
        'title',
        'type',
        'description',
        'findings',
        'recommendations',
        'priority',
        'status',
        'requires_action',
        'reviewed_by',
        'review_comments',
        'reviewed_at',
    ];

    protected $casts = [
        'requires_action' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
