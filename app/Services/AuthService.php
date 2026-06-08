<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Http\Resources\UserResource;
use Carbon\Carbon;
use Exception;
use DB;

class AuthService
{
    /*
        Service Class for AuthController
    */
        
    public function login($data)
    {
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return ['error' => 'The provided credentials are incorrect.'];
        }

        if (!$user->status || $user->email_verified_at == NULL) {
            return ['error' => 'User account is not activated.'];
        }

        $abilities = [$user->role];
        if ($data['email'] == config('constants.admin_email')) {
            $abilities[] = 'master-admin';
        }
        
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        $privileges = null;
        if ($user->role === 'supervisor' || $user->role === 'security-officer') {
            $staffProfile = $user->staffProfile;
            if ($staffProfile) {
                $staffPrivilege = \App\Models\StaffPrivilege::where('staff_profile_id', $staffProfile->id)->first();
                if ($staffPrivilege) {
                    $privileges = $staffPrivilege->privileges;
                }
            }
        }

        return [
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'abilities' => $abilities,
                'privileges' => $privileges,
            ]
        ];
    }

    public function confirmPassword($data)
    {
        $user = User::find($data['user_id']);

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return ['success' => false, 'error' => 'The provided credentials are incorrect.'];
        }

        if (!$user->status) {
            return ['success' => false, 'error' => 'User account is not activated.'];
        }

        return [ 'success' => true ];
    }

    public function forgetPassword($data)
    {
        $status = Password::sendResetLink([
            'email' => $data['email']
        ]);

        return $status === Password::RESET_LINK_SENT
            ? ['message' => 'Password reset link sent successfully to your email.']
            : ['error' => 'Unable to send password reset link.'];
    }

    public function verifyResetPasswordToken($token, $email)
    {
        $passwordReset = DB::table('password_reset_tokens')->where('email', $email)->whereNotNull('token')->orderByDesc('created_at')->first();
        if (!$passwordReset || !Hash::check($token, $passwordReset->token)) {
            return ['success' => false, 'error' => 'Invalid reset token.'];
        }

        $tokenCreationTime = Carbon::make($passwordReset->created_at);
        if ($tokenCreationTime->addMinutes(60)->isPast()) {
            return ['success' => false, 'error' => 'The reset token has expired.'];
        }

        return ['success' => true, 'email' => Carbon::now()->diffInMinutes($tokenCreationTime)];
    }

    public function resetPassword($data)
    {
        $passwordReset = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (!$passwordReset || !Hash::check($data['token'], $passwordReset->token)) {
            return ['error' => 'Invalid or expired reset token.'];
        }

        $tokenCreationTime = Carbon::parse($passwordReset->created_at);
        if (Carbon::now()->diffInMinutes($tokenCreationTime) > 60) {
            return ['error' => 'The reset token has expired. Please request a new one.'];
        }

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return ['error' => 'User not found.'];
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return ['message' => 'Password reset successful.'];
    }

    public function updatePassword($data)
    {
        try {

            $user = User::find($data['user_id']);

            if (!Hash::check($data['current_password'], $advertiser->password)) {
                return ['error' => 'Current password is incorrect'];
            }

            $user->password = Hash::make($data['password']);
            $user->save();

            return ['message' => 'Password update successful.'];
        } catch (Exception $ex) {
            return ['error' => $ex->getMessage()];
        }
    }
    
    public function activateUser(string $email, string $token, string $password)
    {
        try {

            $user = User::where(['api_token' => $token, 'email' => $email])->first();

            if (!$user) {
                return ['error' => 'Invalid or expired activation link'];
            }

            if ($user->status == 1) {
                return ['error' => 'Account already activated'];
            }

            $user->update([
                'password' => Hash::make($password),
                'status' => 1,
                'email_verified_at' => Carbon::now(),
                'api_token' => null,
            ]);

            return ['success' => true];

        } catch (\Throwable $th) {
            Log::error('Activation error: '.$th->getMessage());
            return ['error' => $th->getMessage()];
        }
    }
}
