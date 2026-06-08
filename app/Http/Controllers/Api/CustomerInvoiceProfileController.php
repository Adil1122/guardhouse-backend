<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\CustomerInvoiceProfile;
use App\Models\CustomerProfile;
use App\Services\CustomerInvoiceProfileService;
use App\Http\Resources\CustomerInvoiceProfileResource;
use Illuminate\Http\Request;

class CustomerInvoiceProfileController extends BaseController
{
    protected $filterKey = 'customer_profile_id';
    protected $service;

    public function __construct(CustomerInvoiceProfileService $service)
    {
        parent::__construct(new CustomerInvoiceProfile());
        $this->service = $service;
    }

    public function all()
    {
        $profiles = CustomerInvoiceProfile::with(['customerProfile.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Return raw data for debugging
        $rawProfiles = $profiles->map(function ($profile) {
            return [
                'id' => $profile->id,
                'customer_profile_id' => $profile->customer_profile_id,
                'company_name' => $profile->company_name,
            ];
        });

        if ($profiles->isEmpty()) {
            return response()->json([
                'message' => 'No customer invoice profiles found',
                'data' => [],
                'raw_profiles' => [],
                'count' => 0,
            ], 200);
        }

        return response()->json([
            'data' => CustomerInvoiceProfileResource::collection($profiles),
            'raw_profiles' => $rawProfiles,
            'count' => $profiles->count(),
        ], 200);
    }

}
