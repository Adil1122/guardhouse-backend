<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Shift;

class BindShiftId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shiftId = $request->route('shiftId');

        if ($shiftId) {
            $shift = Shift::find($shiftId);
            if ($shift) {
                $request->merge(['shift_id' => $shiftId]);
                return $next($request);
            }
        }

        return response()->json(['message' => 'Shift not found'], 404);
    }
}
