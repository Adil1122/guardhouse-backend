<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Services\PrivilegeService;
use App\Rules\StaffPrivilegeRule;
use App\Rules\BankDetailRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Rules\EmergencyContactRule;
use App\Models\StaffProfile;
use App\Services\StaffService;

class StaffController extends BaseController
{
    protected $filterKey = null;
    protected $privilegeService;
    protected $service;

    public function __construct(StaffService $service, PrivilegeService $privilegeService)
    {
        parent::__construct(new StaffProfile());
        $this->service = $service;
        $this->privilegeService = $privilegeService;
    }

    public function show(Request $request, $id)
    {
        $response = $this->service->getStaff($id);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->json($response['data'], 200);
    }

    public function update(Request $request, $id)
    {
        if (!$request->has('image') && $request->has('image_path')) {
            $request->merge(['image' => $request->input('image_path')]);
        }

        $validatedData = $request->validate([
            'first_name' => 'nullable|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'email' => ['nullable', 'email', 'max:50', Rule::unique('users', 'email')->ignore($id)],
            'role' => 'nullable|in:admin,supervisor,security-officer',
            'image' => 'nullable|max:5120',
            'image_path' => 'nullable|string|max:255',
            'preferred_first_name' => 'nullable|string|max:50',
            'preferred_last_name' => 'nullable|string|max:50',
            'sia_badge_number' => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:50',
            'emergency_contact' => ['nullable', new EmergencyContactRule()],
            'gender' => 'nullable|in:male,female,other',
            'password' => 'nullable|string|min:8',
        ]);
        
        $response = $this->service->updateStaff($validatedData, $id);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->json($response['data'], 200);
    }

    public function destroy(Request $request, $id)
    {
        $response = $this->service->deleteStaff($id);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->noContent();
    }

    public function listPrivileges(Request $request, $id)
    {
        $result = $this->privilegeService->listPrivileges($id);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 404);
        }

        return response()->json($result, 200);
    }

    public function updatePrivileges(Request $request, $id)
    {
        $validated = Validator::make(array_merge($request->all(), ['id' => $id]), [
            'id' => ['required', Rule::exists('users', 'id')->whereNot('role', 'admin')],
            'privileges' => ['required', 'array', new StaffPrivilegeRule]
        ])->validate();

        $result = $this->privilegeService->updatePrivileges($id, $validated['privileges']);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['success' => true], 200);
    }

    public function listCompliances(Request $request, $userId)
    {
        $result = $this->service->listCompliances($userId);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 404);
        }

        return response()->json($result, 200);
    }

    public function createCompliance(Request $request, $userId)
    {
        $data = array_merge($request->all(), ['id' => $userId]);
        $files = $request->file('files');
        if ($files && !is_array($files)) {
            $files = [$files];
            $data['files'] = $files;
        }

        $validator = Validator::make($data, [
            'id' => ['required', Rule::exists('users', 'id')],
            'compliance_record_id' => 'nullable|exists:staff_compliances,id',
            'compliance_id' => 'required|exists:organization_compliances,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'files' => 'nullable|array',
            'files.*' => 'file|max:5120|mimes:jpg,png,jpeg,docx,pdf',
            'existing_files' => 'nullable|array',
            'existing_files.*' => 'string|max:255'
        ]);

        $validated = $validator->validate();
        
        $result = $this->service->createCompliance($userId, $validated, $files);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json($result, 201);
    }

    public function deleteCompliance(Request $request, $userId)
    {
        $validated = Validator::make(array_merge($request->all(), ['id' => $userId]), [
            'id' => ['required', Rule::exists('users', 'id')],
            'compliance_id' => 'required|exists:staff_compliances,id'
        ])->validate();

        $result = $this->service->deleteCompliance($userId, $validated['compliance_id']);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['success' => true], 200);
    }

    public function updatePaySalary(Request $request, $id)
    {
        $validated = Validator::make(array_merge($request->all(), ['id' => $id]), [
            'id' => ['required', Rule::exists('users', 'id')],
            'bank_details' => ['nullable', new BankDetailRule()],
            'tax_number' => 'nullable|string|max:50',
            'default_pay_group_id' => 'nullable|exists:pay_groups,id',
        ])->validate();

        $result = $this->service->updateSalary($id, $validated);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['success' => true], 200);
    }
}
