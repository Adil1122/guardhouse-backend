<?php

namespace App\Services;

use App\Models\StaffPrivilege;
use App\Models\StaffProfile;

class PrivilegeService
{
    public function listPrivileges(int $id)
    {
        $staff = StaffProfile::with('user')->where('user_id', $id)->first();

        if (!$staff) {
            return ['error' => 'Staff not found'];
        }

        if ($staff->user->role === 'admin') {
            return [
                'editable' => false,
                'items' => ['all'],
                'privileges' => ['all']
            ];
        }

        $staffPrivilege = StaffPrivilege::where('staff_profile_id', $staff->id)->first();

        return [
            'editable' => true,
            'items' => $staffPrivilege ? $staffPrivilege->privileges : []
        ];
    }

    public function updatePrivileges(int $id, array $privileges)
    {
        $staff = StaffProfile::with('user')->where('user_id', $id)->first();

        if (!$staff) {
            return ['error' => 'Staff not found'];
        }

        if ($staff->user->role === 'admin') {
            return ['error' => 'Admin privileges cannot be modified'];
        }

        StaffPrivilege::updateOrCreate(
            ['staff_profile_id' => $staff->id],
            ['privileges' => $privileges]
        );

        return ['success' => true];
    }
}
