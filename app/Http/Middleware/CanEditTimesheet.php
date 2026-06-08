<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Timesheet;
use Carbon\Carbon;

class CanEditTimesheet
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $timesheetId = $request->route('id');
        $timesheet = Timesheet::with('shift.timeclockLogs')->findOrFail($timesheetId);
        $shift = $timesheet->shift;

        if ($shift->timeclockLogs()->exists()) {
            $lockedFields = ['start_date', 'start_time', 'end_date', 'end_time', 'timezone'];

            foreach ($lockedFields as $field) {
                if ($request->has($field)) {
                    $request->request->remove($field);
                }
            }
        }

        if ($request->has('status') && $request->get('status') == 'approved') {
            if ($timesheet->service_group_id == null || $timesheet->pay_group_id == null) {
                abort(403, 'Timesheet has no assigned service group / pay group.');
            }

            $endDateTime = Carbon::parse($timesheet->end_date . ' ' . $timesheet->end_time, $timesheet->timezone);
            $now = Carbon::now($timesheet->timezone);

            if ($now->lt($endDateTime)) {
                abort(403, 'Timesheet cannot be approved before it has ended.');
            }
            
        }

        return $next($request);
    }
}
