<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\CustomerContact;
use App\Services\CustomerContactService;
use Illuminate\Http\Request;

class CustomerContactController extends BaseController
{
    protected $filterKey = 'customer_profile_id';
    protected $service;

    public function __construct(CustomerContactService $service)
    {
        parent::__construct(new CustomerContact());
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate(CustomerContact::getValidationRules('store'));
        
        $response = $this->service->createContact($validatedData);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->json($response['data'], 201);
    }

    public function update(Request $request, $id)
    {
        $contactId = $request->route('id');
        $validatedData = $request->validate(CustomerContact::getValidationRules('update'));
        
        $response = $this->service->updateContact($validatedData, $contactId);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->json($response['data'], 200);
    }

    public function destroy(Request $request, $id)
    {
        $contactId = $request->route('id');
        $response = $this->service->deleteContact($contactId);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->noContent();
    }
}
