<?php

namespace App\Notifications;

use App\Models\TeamMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeamMessageNotification extends Notification
{
    use Queueable;

    public function __construct(private TeamMessage $teamMessage)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'team_message_id' => $this->teamMessage->id,
            'title'            => $this->teamMessage->title,
            'message'          => $this->teamMessage->body,
        ];
    }
}
