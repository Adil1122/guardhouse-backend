<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\Timesheet;
use App\Http\Resources\TimesheetResource;
use App\Services\TimesheetService;

class TimesheetController extends BaseController
{
    protected $filterKey = null;
    protected $service;

    public function __construct(TimesheetService $service)
    {
        parent::__construct(new Timesheet());
        $this->service = $service;
    }

    public function byStatus(Request $request)
    {
        $status = $request->query('status', 'all');
        
        $query = Timesheet::with(['site', 'employeeUser', 'serviceGroup', 'payGroup']);
        
        if ($status && $status !== 'all') {
            if ($status === 'billable') {
                // Return timesheets that are drafted or approved (can be invoiced)
                $query->whereIn('status', ['drafted', 'approved']);
            } else {
                $query->where('status', $status);
            }
        }
        
        $timesheets = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json(TimesheetResource::collection($timesheets), 200);
    }

    public function approve(Request $request, $id)
    {
        $timesheet = Timesheet::find($id);
        
        if (!$timesheet) {
            return response()->json(['message' => 'Timesheet not found'], 404);
        }

        $timesheet->update(['status' => 'approved']);

        return response()->json(['message' => 'Timesheet approved successfully'], 200);
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $timesheet = Timesheet::find($id);
        
        if (!$timesheet) {
            return response()->json(['message' => 'Timesheet not found'], 404);
        }

        $timesheet->update([
            'status' => 'rejected',
            'notes' => $timesheet->notes . "\n[Rejected: " . ($validated['reason'] ?? 'No reason provided') . "]",
        ]);

        return response()->json(['message' => 'Timesheet rejected successfully'], 200);
    }
}
