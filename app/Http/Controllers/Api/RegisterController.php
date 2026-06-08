<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RegisterService;
use App\Rules\EmergencyContactRule;
use App\Rules\BankDetailRule;

class RegisterController extends Controller
{
    protected $service;

    public function __construct(RegisterService $service)
    {
        $this->service = $service;
    }

    public function signup(Request $request)
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        $allowedRoles = ($user?->role === 'admin') ? ['admin', 'supervisor', 'security-officer'] : ['security-officer'];
        if (!$request->has('image') && $request->has('image_path')) {
            $request->merge(['image' => $request->input('image_path')]);
        }

        $validated = $request->validate([
            'role' => 'required|in:' . implode(',', $allowedRoles),
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'image' => 'nullable|max:5120',
            'image_path' => 'nullable|string|max:255',
            'preferred_first_name' => 'required|string|max:50',
            'preferred_last_name' => 'required|string|max:50',
            'sia_badge_number' => 'required|string|max:50',
            'contact_number' => 'required|string|max:50',
            'emergency_contact' => ['nullable', new EmergencyContactRule()],
            'gender' => 'required|in:male,female,other',
            'password' => 'required|string|min:8',
        ]);

        $result = $this->service->signupStaff($validated);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 401);
        }

        return response()->json($result, 201);
    }
}
