<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Services\UserService;
use App\Models\User;
use App\Rules\StrongPassword;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login($request->all());

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 401);
        }

        return response()->json($result, 200);
    }

    public function quickLogin(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email',
            'password' => ['nullable', 'string', 'confirmed', new StrongPassword()],
            'passkey' => 'required|string',
        ]);

        $response = $this->authService->quickLogin($validatedData);
        if(!$response['success']) {
            return response()->json(['message' => $response['error']], $response['error_code']);
        }

        return response()->json($response['data']);
    }
    
    public function confirmPassword(Request $request)
    {
        $validatedData = $request->validate([
            'password' => 'required|string',
            'user_id' => 'required|exists:users,id'
        ]);

        $response = $this->authService->confirmPassword($validatedData);

        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->json(['success' => $response['success']], 200);
    }

    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|exists:users,email',
        ]);

        $result = $this->authService->forgetPassword($request->all());

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 500);
        }

        return response()->json($result, 200);
    }

    public function verifyResetPasswordToken(Request $request)
    {
        $validatedData = $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:password_reset_tokens,email',
        ]);

        $result = $this->authService->verifyResetPasswordToken($validatedData['token'], $validatedData['email']);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json($result, 200);
    }
    

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', new StrongPassword],
        ]);

        $result = $this->authService->resetPassword($request->all());
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json($result, 200);
    }
    
    public function updatePassword(Request $request)
    {
        $validatedData = $request->validate([
            'advertiser_id' => 'required|int|exists:users,id',
            'current_password' => 'required|string',
            'password' => [ 'required', 'confirmed', new StrongPassword ]
        ]);

        $response = $this->authService->updatePassword($validatedData);
        if (isset($response['error'])) {
            return response()->json(['message' => $response['error']], 400);
        }

        return response()->json($response, 200);
    }

    public function activateUser(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => ['required', 'confirmed', new StrongPassword()],
        ]);

        $result = $this->authService->activateUser($validated['email'], $validated['token'], $validated['password']);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['success' => true], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        if ($user) {
            // Revoke all tokens for this user
            $user->tokens()->delete();
            
            return response()->json([
                'message' => 'Logged out successfully'
            ]);
        }
        
        return response()->json([
            'message' => 'No authenticated user found'
        ], 401);
    }
}
