<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Services\CustomerService;
use App\Models\CustomerProfile;

class CustomerController extends BaseController
{
    protected $service;
    protected $filterKey = null;

    public function __construct(CustomerService $service)
    {
        parent::__construct(new CustomerProfile());
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate(CustomerProfile::getValidationRules('store'));
        
        $response = $this->service->createCustomer($validatedData);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->json($response['data'], 201);
    }

    public function show(Request $request, $id)
    {
        $response = $this->service->getCustomer($id);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->json($response['data'], 200);
    }
    
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate(CustomerProfile::getValidationRules('update'));
        
        $response = $this->service->updateCustomer($validatedData, $id);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->json($response['data'], 200);
    }

    public function destroy(Request $request, $id)
    {
        $response = $this->service->deleteCustomer($id);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->noContent();
    }

    public function sitesList(Request $request, $userId)
    {
        $response = $this->service->getSites($request->get('customer_profile_id'));
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->json($response['data'], 200);
    }

    public function attachSite(Request $request, $userId)
    {
        $validatedData = $request->validate([
            'site_id' => 'required|exists:sites,id',
        ]);

        $response = $this->service->attachSite($request->get('customer_profile_id'), $validatedData['site_id']);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->json($response['data'], 200);
    }

    public function detachSite(Request $request, $userId, $id)
    {
        $response = $this->service->detachSite($request->get('customer_profile_id'), $id);
        if(!$response['success']) {
            return response()->json(['success' => false, 'message' => $response['error']], 401);
        }

        return response()->json(['success' => true], 200);
    }
}

