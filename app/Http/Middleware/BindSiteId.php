<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Site;

class BindSiteId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $siteId = $request->route('siteId');

        if ($siteId) {
            $site = Site::find($siteId);
            if ($site) {
                $request->merge(['site_id' => $site->id]);
                return $next($request);
            }
        }

        return response()->json(['message' => 'Site not found'], 404);
    }
}
