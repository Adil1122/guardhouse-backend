<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\Site;
use App\Models\User;
use App\Models\Checkin;
use App\Models\ShiftAlert;
use App\Models\ShiftCheckpointScan;
use App\Models\SiteVisit;
use App\Models\SupervisorReport;
use App\Models\CheckCall;

class SupervisorController extends Controller
{
    // GET supervisor/dashboard
    public function dashboard(Request $request)
    {
        $supervisorId = auth()->id();

        $activeSiteVisit = SiteVisit::where('supervisor_id', $supervisorId)
            ->where('status', 'active')
            ->with('site')
            ->first();

        $siteIds = $this->getSupervisorSiteIds($supervisorId);
        $recentReports = SupervisorReport::where('supervisor_id', $supervisorId)
            ->with('site')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'active_site_visit' => $activeSiteVisit ? $this->formatSiteVisit($activeSiteVisit) : null,
            'statistics' => $this->getStatsData($supervisorId, $siteIds),
            'recent_reports' => $recentReports->map(fn($r) => $this->formatReport($r)),
        ], 200);
    }

    // GET supervisor/active-site-visit
    public function activeSiteVisit(Request $request)
    {
        $visit = SiteVisit::where('supervisor_id', auth()->id())
            ->where('status', 'active')
            ->with('site')
            ->first();

        return response()->json($visit ? $this->formatSiteVisit($visit) : null, 200);
    }

    // GET supervisor/assigned-sites
    public function assignedSites(Request $request)
    {
        $siteIds = $this->getSupervisorSiteIds(auth()->id());
        $sites = Site::whereIn('id', $siteIds)->get();

        return response()->json($sites->map(function ($site) {
            return [
                'id' => (string) $site->id,
                'name' => $site->name,
                'address' => $site->address,
                'status' => $site->status ?? 'Active',
                'assigned_workers' => Shift::where('site_id', $site->id)
                    ->where('status', 'clocked-in')
                    ->count(),
            ];
        }), 200);
    }

    // GET supervisor/workers
    public function workers(Request $request)
    {
        $siteIds = $this->getSupervisorSiteIds(auth()->id());
        $workerIds = Shift::whereIn('site_id', $siteIds)->pluck('assigned_to')->unique()->filter();
        $workers = User::whereIn('id', $workerIds)->get();

        return response()->json($workers->map(function ($user) use ($siteIds) {
            $activeShift = Shift::where('assigned_to', $user->id)
                ->whereIn('site_id', $siteIds)
                ->where('status', 'clocked-in')
                ->with('site')
                ->first();
            return [
                'id' => (string) $user->id,
                'name' => $user->name ?? ($user->first_name . ' ' . $user->last_name),
                'role' => $user->role ?? 'Security Officer',
                'site' => $activeShift?->site?->name ?? '',
                'status' => $activeShift ? 'On Duty' : 'Off Duty',
                'clocked_in' => (bool) $activeShift,
                'shift_start' => $activeShift?->start_time ?? '',
                'shift_end' => $activeShift?->end_time ?? '',
            ];
        }), 200);
    }

    // GET supervisor/all-officers
    public function allOfficers(Request $request)
    {
        return $this->workers($request);
    }

    // GET supervisor/statistics
    public function statistics(Request $request)
    {
        $supervisorId = auth()->id();
        $siteIds = $this->getSupervisorSiteIds($supervisorId);

        return response()->json($this->getStatsData($supervisorId, $siteIds), 200);
    }

    // GET supervisor/recent-reports
    public function recentReports(Request $request)
    {
        $reports = SupervisorReport::where('supervisor_id', auth()->id())
            ->with('site')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($reports->map(fn($r) => $this->formatReport($r)), 200);
    }

    // GET supervisor/reports/{id}
    public function getReport(Request $request, $id)
    {
        $report = SupervisorReport::where('id', $id)
            ->where('supervisor_id', auth()->id())
            ->with('site')
            ->first();

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        return response()->json($this->formatReport($report), 200);
    }

    // POST supervisor/reports
    public function submitReport(Request $request)
    {
        $request->validate([
            'site_id' => 'required|exists:sites,id',
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,critical',
            'findings' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'requires_action' => 'nullable|boolean',
        ]);

        $report = SupervisorReport::create([
            'supervisor_id' => auth()->id(),
            'site_id' => $request->site_id,
            'title' => $request->title,
            'type' => $request->type,
            'description' => $request->description,
            'findings' => $request->findings,
            'recommendations' => $request->recommendations,
            'priority' => $request->input('priority', 'medium'),
            'status' => 'submitted',
            'requires_action' => $request->boolean('requires_action'),
        ]);

        return response()->json(['id' => (string) $report->id, 'message' => 'Report submitted'], 201);
    }

    // POST supervisor/reports/{id}/review
    public function reviewReport(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string',
        ]);

        $report = SupervisorReport::find($id);
        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        $report->update([
            'status' => $request->action === 'approve' ? 'approved' : 'rejected',
            'reviewed_by' => auth()->id(),
            'review_comments' => $request->comments,
            'reviewed_at' => now(),
        ]);

        return response()->json(['message' => 'Report reviewed'], 200);
    }

    // POST supervisor/site-visits/start
    public function startSiteVisit(Request $request)
    {
        $request->validate([
            'site_id' => 'required|exists:sites,id',
            'location' => 'nullable|string',
            'purpose' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        SiteVisit::where('supervisor_id', auth()->id())->where('status', 'active')->update(['status' => 'ended', 'ended_at' => now()]);

        $visit = SiteVisit::create([
            'supervisor_id' => auth()->id(),
            'site_id' => $request->site_id,
            'location' => $request->location,
            'purpose' => $request->purpose,
            'notes' => $request->notes,
            'started_at' => now(),
            'status' => 'active',
        ]);

        return response()->json($this->formatSiteVisit($visit->load('site')), 201);
    }

    // POST supervisor/site-visits/end
    public function endSiteVisit(Request $request)
    {
        $request->validate([
            'summary' => 'nullable|string',
            'has_issues' => 'nullable|boolean',
            'issues_description' => 'nullable|string',
        ]);

        $visit = SiteVisit::where('supervisor_id', auth()->id())->where('status', 'active')->first();
        if (!$visit) {
            return response()->json(['message' => 'No active site visit'], 404);
        }

        $visit->update([
            'status' => 'ended',
            'ended_at' => now(),
            'summary' => $request->summary,
            'has_issues' => $request->boolean('has_issues'),
            'issues_description' => $request->issues_description,
        ]);

        return response()->json(['message' => 'Site visit ended'], 200);
    }

    // GET supervisor/notifications
    public function notifications(Request $request)
    {
        $notifications = auth()->user()->notifications()->orderBy('created_at', 'desc')->get();
        return response()->json($notifications->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'data' => $n->data,
                'read' => !is_null($n->read_at),
                'created_at' => $n->created_at,
            ];
        }), 200);
    }

    // POST supervisor/notifications/{id}/read
    public function markNotificationRead(Request $request, $id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['message' => 'Marked as read'], 200);
    }

    // POST supervisor/notifications/read-all
    public function markAllNotificationsRead(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All marked as read'], 200);
    }

    // DELETE supervisor/notifications/{id}
    public function deleteNotification(Request $request, $id)
    {
        auth()->user()->notifications()->where('id', $id)->delete();
        return response()->json(['message' => 'Notification deleted'], 200);
    }

    // POST supervisor/workers/{id}/tasks/assign
    public function assignTask(Request $request, $id)
    {
        $request->validate([
            'shift_id' => 'nullable|exists:shifts,id',
            'notes' => 'nullable|string',
        ]);

        return response()->json(['message' => 'Task assigned'], 200);
    }

    // POST supervisor/checkins
    public function submitCheckin(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'type' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $checkin = Checkin::create([
            'shift_id' => $request->shift_id,
            'user_id' => auth()->id(),
            'type' => $request->input('type', 'regular'),
            'notes' => $request->notes,
            'latitude' => $request->input('latitude', 0),
            'longitude' => $request->input('longitude', 0),
            'checked_in_at' => now(),
            'inside_geofence' => true,
        ]);

        return response()->json(['message' => 'Check-in recorded', 'id' => $checkin->id], 201);
    }

    // POST supervisor/qr-scans
    public function saveQrScan(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        $checkpoint = \App\Models\SiteCheckpoint::where('qr_code', $request->qr_code)->first();

        $scan = ShiftCheckpointScan::create([
            'site_checkpoint_id' => $checkpoint?->id,
            'user_id' => auth()->id(),
            'scanned_at' => now(),
        ]);

        return response()->json(['message' => 'QR scan saved', 'id' => $scan->id], 201);
    }

    // GET supervisor/alarms
    public function alarmHistory(Request $request)
    {
        $siteIds = $this->getSupervisorSiteIds(auth()->id());
        $shiftIds = Shift::whereIn('site_id', $siteIds)->pluck('id');

        $alerts = ShiftAlert::whereIn('shift_id', $shiftIds)
            ->with(['user', 'shift'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($alerts->map(function ($alert) {
            return [
                'id' => $alert->id,
                'type' => $alert->type,
                'status' => $alert->response_status ?? 'raised',
                'timestamp' => $alert->created_at,
                'user_name' => $alert->user?->name ?? '',
            ];
        }), 200);
    }

    // POST supervisor/alarms
    public function raiseAlarm(Request $request)
    {
        $siteIds = $this->getSupervisorSiteIds(auth()->id());
        $shift = Shift::whereIn('site_id', $siteIds)->where('status', 'clocked-in')->first();
        $shiftId = $shift?->id ?? $request->input('shift_id');

        if (!$shiftId) {
            return response()->json(['message' => 'No active shift. Please provide shift_id.'], 422);
        }

        $alert = ShiftAlert::create([
            'shift_id' => $shiftId,
            'user_id' => auth()->id(),
            'type' => $request->input('type', 'in-app-notification'),
            'response_status' => null,
        ]);

        return response()->json(['message' => 'Alarm raised', 'id' => $alert->id], 201);
    }

    // GET admin/alarms
    public function adminAlarmHistory(Request $request)
    {
        $alerts = ShiftAlert::with(['user', 'shift'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($alerts->map(function ($alert) {
            return [
                'id'        => $alert->id,
                'type'      => $alert->type,
                'status'    => $alert->response_status ?? 'raised',
                'timestamp' => $alert->created_at,
                'user_name' => $alert->user?->name ?? '',
            ];
        }), 200);
    }

    // POST admin/alarms
    public function adminRaiseAlarm(Request $request)
    {
        $shiftId = $request->input('shift_id');

        if (!$shiftId) {
            // Find any currently active shift
            $shift = Shift::whereIn('status', ['clocked-in', 'checking-welfare'])->first();
            $shiftId = $shift?->id;
        }

        if (!$shiftId) {
            return response()->json(['message' => 'No active shift found. Please provide shift_id.'], 422);
        }

        $alert = ShiftAlert::create([
            'shift_id'        => $shiftId,
            'user_id'         => auth()->id(),
            'type'            => $request->input('type', 'in-app-notification'),
            'response_status' => null,
        ]);

        return response()->json(['message' => 'Alarm raised', 'id' => $alert->id], 201);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getSupervisorSiteIds($supervisorId): array
    {
        return Shift::where('assigned_to', $supervisorId)
            ->pluck('site_id')
            ->merge(
                Shift::where('created_by', $supervisorId)->pluck('site_id')
            )
            ->unique()
            ->toArray();
    }

    private function getStatsData($supervisorId, array $siteIds): array
    {
        $workerIds = Shift::whereIn('site_id', $siteIds)->pluck('assigned_to')->unique()->filter();
        return [
            'total_workers' => $workerIds->count(),
            'active_sites' => count($siteIds),
            'reports_today' => SupervisorReport::where('supervisor_id', $supervisorId)
                ->whereDate('created_at', today())
                ->count(),
            'active_shifts' => Shift::whereIn('site_id', $siteIds)->where('status', 'clocked-in')->count(),
        ];
    }

    // POST supervisor/workers/{id}/check-calls
    public function sendCheckCall(Request $request, $id)
    {
        $worker = User::find($id);
        if (!$worker) {
            return response()->json(['message' => 'Worker not found'], 404);
        }

        $call = CheckCall::create([
            'worker_id' => $id,
            'status' => 'pending',
            'scheduled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Check call sent',
            'check_call' => [
                'id' => $call->id,
                'worker_id' => $call->worker_id,
                'status' => $call->status,
                'timestamp' => $call->scheduled_at->format('M j, Y H:i'),
                'scheduled_at' => $call->scheduled_at,
            ],
        ], 201);
    }

    private function formatSiteVisit($visit): array
    {
        return [
            'id' => $visit->id,
            'site_id' => $visit->site_id,
            'site_name' => $visit->site?->name ?? '',
            'location' => $visit->location,
            'purpose' => $visit->purpose,
            'notes' => $visit->notes,
            'start_time' => $visit->started_at,
            'status' => $visit->status,
        ];
    }

    private function formatReport($report): array
    {
        return [
            'id' => (string) $report->id,
            'title' => $report->title,
            'type' => $report->type,
            'site' => $report->site?->name ?? '',
            'site_id' => $report->site_id,
            'priority' => $report->priority,
            'status' => ucfirst($report->status),
            'created_at' => $report->created_at?->toISOString(),
        ];
    }
}
