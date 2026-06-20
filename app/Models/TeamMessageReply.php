<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMessageReply extends Model
{
    protected $fillable = [
        'team_message_id',
        'thread_user_id',
        'sender_id',
        'body',
    ];

    public function message()
    {
        return $this->belongsTo(TeamMessage::class, 'team_message_id');
    }

    public function threadUser()
    {
        return $this->belongsTo(User::class, 'thread_user_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
