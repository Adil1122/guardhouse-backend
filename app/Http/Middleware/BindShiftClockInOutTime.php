<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Shift;
use App\Services\ShiftTimeclockLogService;
use Auth;

class BindShiftClockInOutTime
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $shift = Shift::findOrFail($request->route('shiftId'));
        $service = app(ShiftTimeclockLogService::class);

        $action = $request->action ?? null;
        if (!$action || !in_array($action, ['clock-in', 'clock-out'])) {
            abort(422, 'Invalid action for timeclock.');
        }

        $canClock = $service->canClockInOut($user, $shift, $action);
        if (!$canClock) {
            abort(403, 'You are not allowed to perform this action.');
        }

        $dateTime = $service->resolveClockInOutDateTime($shift);

        if ($action === 'clock-in') {
            $request->merge([
                'clocked_in' => $dateTime,
                'marked_by'  => $user->id,
            ]);
        } elseif ($action === 'clock-out') {
            $request->merge([
                'clocked_out'   => $dateTime,
                'marked_by'     => $user->id,
                'work_duration' => $service->calculateWorkDuration($shift, $dateTime),
            ]);
        }

        return $next($request);
    }
}
