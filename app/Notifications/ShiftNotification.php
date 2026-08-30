<?php

namespace App\Notifications;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Base class for the worker-facing shift lifecycle notifications
 * (assigned / changed / cancelled / start reminder).
 *
 * Delivered on the `database` channel only. The Flutter app surfaces these as
 * system notifications by polling `worker/notifications` in the background
 * (native AlarmManager) and in the foreground.
 */
abstract class ShiftNotification extends Notification
{
    use Queueable;

    public function __construct(protected Shift $shift)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    abstract protected function title(): string;

    abstract protected function body(): string;

    /**
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable): array
    {
        return [
            'shift_id' => $this->shift->id,
            'type' => 'shift',
            'title' => $this->title(),
            'message' => $this->body(),
        ];
    }

    protected function siteName(): string
    {
        return $this->shift->site?->name ?? 'your site';
    }

    /** e.g. "Mon, 1 Sep at 08:00" */
    protected function whenLabel(): string
    {
        $date = $this->shift->start_date
            ? Carbon::parse($this->shift->start_date)->format('D, j M')
            : '';
        $time = $this->shift->start_time ? substr($this->shift->start_time, 0, 5) : '';

        if ($date && $time) {
            return "{$date} at {$time}";
        }

        return $date ?: $time;
    }
}
