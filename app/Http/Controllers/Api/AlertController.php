<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShiftAlert;

class AlertController extends Controller
{
    // GET admin/alerts
    public function index(Request $request)
    {
        $alerts = ShiftAlert::with(['user', 'shift'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($alerts->map(function ($alert) {
            return [
                'id' => $alert->id,
                'type' => $alert->type,
                'status' => $alert->response_status ?? 'raised',
                'timestamp' => $alert->created_at,
                'shift_id' => $alert->shift_id,
                'user_name' => $alert->user?->name ?? '',
            ];
        }), 200);
    }

    // POST admin/alerts/{id}/read
    public function markRead(Request $request, $id)
    {
        $alert = ShiftAlert::find($id);
        if (!$alert) {
            return response()->json(['message' => 'Alert not found'], 404);
        }
        $alert->update(['response_status' => 'replied', 'response_timestamp' => now()]);
        return response()->json(['message' => 'Alert marked as read'], 200);
    }

    // POST admin/alerts/read-all
    public function markAllRead(Request $request)
    {
        ShiftAlert::whereNull('response_status')->update([
            'response_status' => 'replied',
            'response_timestamp' => now(),
        ]);
        return response()->json(['message' => 'All alerts marked as read'], 200);
    }

    // DELETE admin/alerts/{id}
    public function destroy(Request $request, $id)
    {
        $alert = ShiftAlert::find($id);
        if (!$alert) {
            return response()->json(['message' => 'Alert not found'], 404);
        }
        $alert->delete();
        return response()->json(['message' => 'Alert deleted'], 200);
    }
}
