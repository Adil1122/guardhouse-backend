<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\StaffProfile;
use App\Models\StaffPrivilege;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Log;
use App\Events\UserCreated;
use Illuminate\Support\Facades\Event;

class RegisterService
{
    public function signupStaff(array $data)
    {
        try {
            $user = DB::transaction(function () use ($data) {
                $imagePath = $data['image'] ?? null;
                if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $data['image']->store('profile-images', 'public');
                } elseif (is_string($imagePath) && str_starts_with($imagePath, 'http')) {
                    $imagePath = null;
                }

                $user = User::create([
                    'role' => $data['role'],
                    'email' => $data['email'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'image' => $imagePath,
                    'password' => $data['password'] ?? null,
                    'status' => 1,
                    'email_verified_at' => now(),
                ]);

                $staffProfile = StaffProfile::create([
                    'user_id' => $user->id,
                    'preferred_first_name' => $data['preferred_first_name'],
                    'preferred_last_name' => $data['preferred_last_name'],
                    'sia_badge_number' => $data['sia_badge_number'],
                    'contact_number' => $data['contact_number'],
                    'emergency_contact' => $data['emergency_contact'] ?? null,
                    'gender' => $data['gender']
                ]);

                $this->assignPrivileges($staffProfile->id, $data['role']);
                return $user;
            });

            Event::dispatch(new UserCreated($user));

            return [
                'success' => true,
                'data' => new UserResource($user)
            ];
        } catch (\Throwable $th) {
            Log::error('Signup error: '.$th->getMessage());
            return ['error' => $th->getMessage()];
        }
    }

    private function assignPrivileges($staffProfileId, $role)
    {
        if ($role === 'admin') {

            StaffPrivilege::updateOrCreate(
                ['staff_profile_id' => $staffProfileId],
                ['privileges' => ['all' => 'all']]
            );

        } elseif ($role === 'supervisor') {

            StaffPrivilege::updateOrCreate(
                ['staff_profile_id' => $staffProfileId],
                ['privileges' => []]
            );

        } elseif ($role === 'security-officer') {

            StaffPrivilege::updateOrCreate(
                ['staff_profile_id' => $staffProfileId],
                ['privileges' => []]
            );
        }
    }
}
