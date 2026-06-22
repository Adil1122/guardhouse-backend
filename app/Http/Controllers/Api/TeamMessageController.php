<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeamMessage;
use App\Models\TeamMessageReply;
use App\Models\User;
use App\Notifications\TeamMessageNotification;
use App\Notifications\TeamMessageReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class TeamMessageController extends Controller
{
    private function format($msg): array
    {
        $creator = is_object($msg->creator ?? null) ? $msg->creator : null;
        $createdAt = $msg->created_at ?? null;
        return [
            'id'         => $msg->id,
            'title'      => $msg->title,
            'body'       => $msg->body,
            'created_by' => $creator ? trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? '')) : 'Admin',
            'created_at' => $createdAt instanceof \Carbon\Carbon ? $createdAt->toISOString() : (string) $createdAt,
        ];
    }

    private function formatReply($reply): array
    {
        $sender = is_object($reply->sender ?? null) ? $reply->sender : null;
        $createdAt = $reply->created_at ?? null;
        return [
            'id'              => $reply->id,
            'team_message_id' => $reply->team_message_id,
            'thread_user_id'  => $reply->thread_user_id,
            'sender_id'       => $reply->sender_id,
            'sender_name'     => $sender ? trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? '')) : '',
            'is_admin'        => $sender ? in_array($sender->role, ['admin', 'master-admin']) : false,
            'body'            => $reply->body,
            'created_at'      => $createdAt instanceof \Carbon\Carbon ? $createdAt->toISOString() : (string) $createdAt,
        ];
    }

    private function unreadMessageIds($user): array
    {
        return $user->unreadNotifications()
            ->where('type', TeamMessageNotification::class)
            ->get()
            ->map(fn($n) => (string) ($n->data['team_message_id'] ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    // POST worker/team-messages/{id}/mark-read, POST supervisor/team-messages/{id}/mark-read
    public function markRead($messageId)
    {
        $user = auth()->user();
        $notifications = $user->unreadNotifications()
            ->where('type', TeamMessageNotification::class)
            ->get()
            ->filter(fn($n) => (string) ($n->data['team_message_id'] ?? '') === (string) $messageId);

        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Marked as read']);
    }

    // GET worker/team-messages/{id}/replies, GET supervisor/team-messages/{id}/replies
    public function myReplies($messageId)
    {
        $replies = TeamMessageReply::with('sender')
            ->where('team_message_id', $messageId)
            ->where('thread_user_id', auth()->id())
            ->orderBy('created_at')
            ->get();

        return response()->json(['replies' => $replies->map(fn($r) => $this->formatReply($r))]);
    }

    // POST worker/team-messages/{id}/replies, POST supervisor/team-messages/{id}/replies
    public function storeReply(Request $request, $messageId)
    {
        $request->validate(['body' => 'required|string']);

        $reply = TeamMessageReply::create([
            'team_message_id' => $messageId,
            'thread_user_id'  => auth()->id(),
            'sender_id'       => auth()->id(),
            'body'            => $request->body,
        ]);
        $reply->load('sender');

        $admins = User::whereIn('role', ['admin', 'master-admin'])->get();
        Notification::send($admins, new TeamMessageReplyNotification($reply));

        return response()->json(['message' => 'Reply sent', 'data' => $this->formatReply($reply)], 201);
    }

    // GET admin/team-messages/{id}/replies
    public function adminReplies($messageId)
    {
        $replies = TeamMessageReply::with('sender', 'threadUser')
            ->where('team_message_id', $messageId)
            ->orderBy('created_at')
            ->get();

        $threads = [];
        foreach ($replies as $reply) {
            $tid = $reply->thread_user_id;
            if (!isset($threads[$tid])) {
                $threadUser = $reply->threadUser;
                $threads[$tid] = [
                    'thread_user_id'   => $tid,
                    'thread_user_name' => $threadUser ? trim(($threadUser->first_name ?? '') . ' ' . ($threadUser->last_name ?? '')) : 'Unknown',
                    'replies'          => [],
                ];
            }
            $threads[$tid]['replies'][] = $this->formatReply($reply);
        }

        return response()->json(['threads' => array_values($threads)]);
    }

    // POST admin/team-messages/{id}/replies
    public function adminStoreReply(Request $request, $messageId)
    {
        $request->validate([
            'body'           => 'required|string',
            'thread_user_id' => 'required|integer',
        ]);

        $reply = TeamMessageReply::create([
            'team_message_id' => $messageId,
            'thread_user_id'  => $request->thread_user_id,
            'sender_id'       => auth()->id(),
            'body'            => $request->body,
        ]);
        $reply->load('sender');

        $threadUser = User::find($request->thread_user_id);
        if ($threadUser) {
            $threadUser->notify(new TeamMessageReplyNotification($reply));
        }

        return response()->json(['message' => 'Reply sent', 'data' => $this->formatReply($reply)], 201);
    }

    // GET admin/team-messages
    public function adminIndex()
    {
        $messages = TeamMessage::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['messages' => $messages->map(fn($m) => $this->format($m))]);
    }

    // POST admin/team-messages
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string',
        ]);

        $msg = TeamMessage::create([
            'created_by' => auth()->id(),
            'title'      => $request->title,
            'body'       => $request->body,
        ]);
        $msg->load('creator');

        $recipients = User::whereIn('role', ['security-officer', 'supervisor'])->get();
        Notification::send($recipients, new TeamMessageNotification($msg));

        return response()->json(['message' => 'Message sent', 'data' => $this->format($msg)], 201);
    }

    // DELETE admin/team-messages/{id}
    public function destroy($id)
    {
        $msg = TeamMessage::findOrFail($id);
        $msg->delete();
        return response()->json(['message' => 'Message deleted']);
    }

    // GET worker/team-messages  (same for supervisor)
    public function index()
    {
        $messages = TeamMessage::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadIds = $this->unreadMessageIds(auth()->user());

        return response()->json(['messages' => $messages->map(function ($m) use ($unreadIds) {
            $formatted = $this->format($m);
            $formatted['is_unread'] = in_array((string) $m->id, $unreadIds, true);
            return $formatted;
        })]);
    }
}
