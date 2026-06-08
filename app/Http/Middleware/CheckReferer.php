<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckReferer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $allowedDomains = config('constants.allowed_referers'); 
        $referer = $request->headers->get('referer');

        // Allow mobile requests without referer header
        if (!$referer) {
            // Check if it's an API request from mobile app
            $userAgent = $request->headers->get('user-agent');
            if ($userAgent && (strpos($userAgent, 'okhttp') !== false || strpos($userAgent, 'dart') !== false)) {
                return $next($request);
            }
            
            // For API requests, allow without referer for mobile apps
            if ($request->is('api/*')) {
                return $next($request);
            }
            
            return response()->json(['message' => 'Request not allowed'], 403);
        }

        // Extract host from referer, fallback to the referer itself if parse_url fails
        $refererHost = parse_url($referer, PHP_URL_HOST) ?? $referer;
        // Strip protocol and trailing slashes if fallback was used
        $refererHost = preg_replace('/^https?:\/\//', '', $refererHost);
        $refererHost = rtrim($refererHost, '/');

        $isAllowed = false;

        foreach ($allowedDomains as $allowed) {
            // Clean the allowed domain string (remove quotes, spaces)
            $allowed = trim($allowed, " \t\n\r\0\x0B\"'");
            $allowedHost = parse_url($allowed, PHP_URL_HOST) ?? $allowed;
            // Strip protocol and trailing slashes for comparison
            $allowedHost = preg_replace('/^https?:\/\//', '', $allowedHost);
            $allowedHost = rtrim($allowedHost, '/');

            if ($refererHost === $allowedHost) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return response()->json(['message' => 'Request not allowed'], 403);
        }

        return $next($request);
    }
}
