<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CustomerProfile;
use App\Models\User;

class BindCustomerProfileId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->route('userId') ?? $request->input('userId');
        $isValidCustomer = User::where(['id' => $userId, 'role' => 'customer'])->exists();

        if ($userId && $isValidCustomer) {
            $customerProfile = CustomerProfile::where('user_id', $userId)->first();
            if ($customerProfile) {
                $request->merge(['customer_profile_id' => $customerProfile->id]);
                return $next($request);
            }
        }

        return response()->json(['message' => 'Customer profile not found'], 404);
    }
}
