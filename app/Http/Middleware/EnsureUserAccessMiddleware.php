<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\StaffPrivilege;
use Auth;

class EnsureUserAccessMiddleware
{
    /**
     * 1. Ensures user is authenticated
     * 2. Check privileges / permissions
     * 3. Binds user_id into request
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $isAllowed = false;

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role === 'admin') {
            $isAllowed = true;
        } else if ($user->role === 'supervisor' || $user->role === 'security-officer') {

            // For worker geofence routes, allow access without staff profile check
            $routePath = $request->route()->uri();
            if ($routePath && str_contains($routePath, 'worker')) {
                $isAllowed = true;
            } else {
                $staffProfile = $user->staffProfile;
                if (!$staffProfile) {
                    return response()->json(['message' => 'Staff profile not found'], 403);
                }

                $isAllowed = $this->checkPrivilege($request, $staffProfile->id);
            }
        }


        if ($isAllowed) {
            $request->merge(['user_id' => $user->id]);
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
    /**
     * Check if user has privilege to access the route
     * 
     * @param Request $request
     * @param int $staffProfileId
     * @return bool
     */
    private function checkPrivilege(Request $request, $staffProfileId): bool
    {
        $routeName = $request->route()->getName();

        if (!$routeName) {
            return false;
        }

        $parts = explode('.', $routeName);
        if (count($parts) < 2) {
            return false;
        }

        $itemId = $parts[0];
        $requiredPrivilege = $parts[1];

        $staffPrivilege = StaffPrivilege::where('staff_profile_id', $staffProfileId)->first();

        if (!$staffPrivilege || !$staffPrivilege->privileges) {
            return false;
        }

        if ($itemId == 'live_operations' && $requiredPrivilege == 'list') {
            if ($staffProfile->user->role === 'supervisor') {
                return true;
            } else {
                return false;
            }
        }

        $userPrivileges = $staffPrivilege->privileges;

        if (isset($userPrivileges['all']) && ($userPrivileges['all'] === 'all' || (is_array($userPrivileges['all']) && in_array('all', $userPrivileges['all'])))) {
            return true;
        }

        if (isset($userPrivileges[$itemId])) {
            $itemPerms = $userPrivileges[$itemId];
            
            if (is_array($itemPerms)) {
                return in_array('all', $itemPerms) || in_array($requiredPrivilege, $itemPerms);
            }
            
            return $itemPerms === 'all' || $itemPerms === $requiredPrivilege;
        }

        return false;
    }
}
