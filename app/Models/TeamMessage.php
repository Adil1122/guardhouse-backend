<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMessage extends Model
{
    protected $fillable = ['created_by', 'title', 'body'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function replies()
    {
        return $this->hasMany(TeamMessageReply::class, 'team_message_id');
    }
}
