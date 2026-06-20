<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeamMessage;
use App\Models\TeamMessageReply;
use Illuminate\Http\Request;

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

        return response()->json(['message' => 'Reply sent', 'data' => $this->formatReply($reply->load('sender'))], 201);
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

        return response()->json(['message' => 'Reply sent', 'data' => $this->formatReply($reply->load('sender'))], 201);
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

        return response()->json(['message' => 'Message sent', 'data' => $this->format($msg->load('creator'))], 201);
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

        return response()->json(['messages' => $messages->map(fn($m) => $this->format($m))]);
    }
}
