<?php

namespace App\Notifications;

use App\Models\TeamMessageReply;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeamMessageReplyNotification extends Notification
{
    use Queueable;

    public function __construct(private TeamMessageReply $reply)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $sender = $this->reply->sender;
        $senderName = $sender ? trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? '')) : 'Someone';

        return [
            'team_message_id' => $this->reply->team_message_id,
            'reply_id'         => $this->reply->id,
            'title'            => 'New reply from ' . $senderName,
            'message'          => $this->reply->body,
        ];
    }
}
