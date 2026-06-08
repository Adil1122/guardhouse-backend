<?php

namespace App\Services;

use App\Models\User;
use App\Models\StaffProfile;
use App\Models\StaffCompliance;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\StaffComplianceResource;
use App\Http\Resources\StaffProfileResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StaffService
{
    public function deleteStaff(int $id)
    {
        try {
            $user = User::where(['id' => $id])->whereIn('role', ['supervisor', 'security-officer', 'worker'])->first();

            if (!$user) {
                return ['success' => false, 'error' => "Staff not found"];
            }
            
            $user->delete();

            return [
                'success' => true,
                'data' => []
            ];
        } catch (\Throwable $th) {
            Log::error('Staff delete error: ' . $th->getMessage());
            return ['success' => false, 'error' => $th->getMessage()];
        }
    }

    public function getStaff(int $id)
    {
        try {
            $user = User::where(['id' => $id])->whereIn('role', ['supervisor', 'security-officer', 'worker'])->first();

            if (!$user) {
                return ['success' => false, 'error' => "Staff not found"];
            }

            $profile = StaffProfile::with(['privileges', 'compliances.compliance'])->where('user_id', $user->id)->first();

            if (!$profile) {
                 // If no profile exists, create one or just return the user info? 
                 // Returning the user info wrapped in a resource might be better.
                 // But for consistency with getCustomer, we expect a profile.
                return ['success' => false, 'error' => "Profile not found"];
            }

            return [
                'success' => true,
                'data' => new StaffProfileResource($profile)
            ];
        } catch (\Throwable $th) {
            Log::error('Get staff error: ' . $th->getMessage());
            return ['success' => false, 'error' => $th->getMessage()];
        }
    }
    public function updateStaff(array $data, int $id)
    {
        try {
            $profile = DB::transaction(function () use ($data, $id) {

                $user = User::findOrFail($id);

                // handle image upload if it's a file
                $imagePath = $data['image'] ?? null;
                if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $data['image']->store('profile-images', 'public');
                }

                // update user columns only if data key exists
                $userUpdateData = array_filter([
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'role' => $data['role'] ?? null,
                    'image' => $imagePath,
                    'password' => $data['password'] ?? null,
                ], function ($value, $key) use ($data, $imagePath) {
                    if ($key === 'image') {
                        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                            return true;
                        }
                        if (is_string($value) && str_starts_with($value, 'http')) {
                            return false;
                        }
                    }
                    if ($key === 'password' && empty($value)) {
                        return false;
                    }
                    return array_key_exists($key, $data);
                }, ARRAY_FILTER_USE_BOTH);
                
                if (!empty($userUpdateData)) {
                    $user->update($userUpdateData);
                }
                
                $updateData = array_filter([
                    'preferred_first_name' => $data['preferred_first_name'] ?? null,
                    'preferred_last_name' => $data['preferred_last_name'] ?? null,
                    'sia_badge_number' => $data['sia_badge_number'] ?? null,
                    'contact_number' => $data['contact_number'] ?? null,
                    'emergency_contact' => $data['emergency_contact'] ?? null,
                    'gender' => $data['gender'] ?? null,
                ], function ($value, $key) use ($data) {
                    return array_key_exists($key, $data);
                }, ARRAY_FILTER_USE_BOTH);

                $profile = StaffProfile::updateOrCreate(['user_id' => $user->id], $updateData);
                return $profile;
            });

            return [
                'success' => true,
                'data' => new StaffProfileResource($profile)
            ];

        } catch (\Throwable $th) {
            Log::error('Staff update error: ' . $th->getMessage());

            return [
                'success' => false,
                'error' => $th->getMessage()
            ];
        }
    }
    public function listCompliances($userId)
    {
        $staff = StaffProfile::with(['compliances.compliance'])->where('user_id', $userId)->first();
        if (!$staff) {
            return ['error' => 'Staff profile not found'];
        }

        return StaffComplianceResource::collection($staff->compliances);
    }

    public function createCompliance($userId, $data, $files = [])
    {
        $staff = StaffProfile::where('user_id', $userId)->first();
        if (!$staff) {
            return ['error' => 'Staff profile not found'];
        }

        $filenames = [];
        if ($files) {
            foreach ($files as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('compliance-documents', $filename, 'public');
                $filenames[] = $filename;
            }
        }

        $existingFiles = [];
        if (isset($data['existing_files']) && is_array($data['existing_files'])) {
            $existingFiles = $data['existing_files'];
        }

        $allFiles = array_values(array_unique(array_merge($existingFiles, $filenames)));

        if (!empty($data['compliance_record_id'])) {
            $compliance = StaffCompliance::where('staff_profile_id', $staff->id)
                ->where('id', $data['compliance_record_id'])
                ->first();

            if (!$compliance) {
                return ['error' => 'Compliance record not found'];
            }

            $compliance->update([
                'compliance_id' => $data['compliance_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'files' => $allFiles,
            ]);
        } else {
            $compliance = StaffCompliance::create([
                'staff_profile_id' => $staff->id,
                'compliance_id' => $data['compliance_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'files' => $allFiles
            ]);
        }

        return new StaffComplianceResource($compliance->load('compliance'));
    }

    public function deleteCompliance($userId, $complianceId)
    {
        $staff = StaffProfile::where('user_id', $userId)->first();
        if (!$staff) {
            return ['error' => 'Staff profile not found'];
        }

        $compliance = StaffCompliance::where('staff_profile_id', $staff->id)->find($complianceId);
        if (!$compliance) {
            return ['error' => 'Compliance record not found'];
        }

        if ($compliance->files && is_array($compliance->files)) {
            foreach ($compliance->files as $file) {
                Storage::delete('public/compliance-documents/' . $file);
            }
        }

        $compliance->delete();

        return ['success' => true];
    }

    public function updateSalary($userId, $data)
    {
        $staff = StaffProfile::where('user_id', $userId)->first();
        if (!$staff) {
            return ['error' => 'Staff profile not found'];
        }

        $staff->update($data);

        return ['success' => true];
    }
}