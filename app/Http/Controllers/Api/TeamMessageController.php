<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeamMessage;
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
