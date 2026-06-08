<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Site;
use App\Models\CustomerProfile;
use App\Http\Resources\UserResource;
use App\Http\Resources\CustomerProfileResource;
use App\Http\Resources\SiteResource;
use Illuminate\Support\Facades\Log;
use App\Events\UserCreated;
use Illuminate\Support\Facades\Event;

class CustomerService
{
    public function createCustomer(array $data)
    {
        try {
            $user = DB::transaction(function () use ($data) {
                $user = User::create([
                    'role' => 'customer',
                    'email' => $data['email'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                ]);

                $profile = CustomerProfile::create([
                    'user_id' => $user->id,
                    'reference_number' => $data['reference_number'] ?? null,
                    'address' => $data['address'] ?? null,
                    'default_service_group_id' => $data['default_service_group_id'] ?? null,
                ]);

                return $user;
            });

            Event::dispatch(new UserCreated($user));

            return [
                'success' => true,
                'data' => new UserResource($user)
            ];
        } catch (\Throwable $th) {
            Log::error('Customer signup error: '.$th->getMessage());
            return ['success' => false, 'error' => $th->getMessage()];
        }
    }
    
    public function updateCustomer(array $data, int $id)
    {
        try {
            $profile = DB::transaction(function () use ($data, $id) {

                $user = User::findOrFail($id);

                $user->update([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                ]);

                $profile = CustomerProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'reference_number' => $data['reference_number'] ?? null,
                        'address' => $data['address'] ?? null,
                        'default_service_group_id' => $data['default_service_group_id'] ?? null,
                    ]
                );

                return $profile;
            });

            return [
                'success' => true,
                'data' => new CustomerProfileResource($profile)
            ];

        } catch (\Throwable $th) {
            Log::error('Customer update error: ' . $th->getMessage());

            return [
                'success' => false,
                'error' => $th->getMessage()
            ];
        }
    }
    
    public function deleteCustomer(int $id)
    {
        try {

            $user = User::where(['role' => 'customer', 'id' => $id])->first();

            if (!$user) {
                return ['success' => false, 'error' => "User not found"];
            }
            
            $user->delete();

            return [
                'success' => true,
                'data' => []
            ];
        } catch (\Throwable $th) {
            Log::error('Customer delete error: ' . $th->getMessage());
            return ['success' => false, 'error' => $th->getMessage()];
        }
    }

    public function getCustomer(int $id)
    {
        try {
            $user = User::where(['role' => 'customer', 'id' => $id])->first();

            if (!$user) {
                return ['success' => false, 'error' => "User not found"];
            }

            $profile = CustomerProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return ['success' => false, 'error' => "Profile not found"];
            }

            return [
                'success' => true,
                'data' => new CustomerProfileResource($profile)
            ];
        } catch (\Throwable $th) {
            Log::error('Get customer error: ' . $th->getMessage());
            return ['success' => false, 'error' => $th->getMessage()];
        }
    }

    public function getSites(int $customerProfileId)
    {
        try {
            $customerProfile = CustomerProfile::findOrFail($customerProfileId);
            $sites = Site::where('customer_profile_id', $customerProfileId)
                ->orWhereNull('customer_profile_id')
                ->get();

            return [
                'success' => true,
                'data' => SiteResource::collection($sites)
            ];
        } catch (\Throwable $th) {
            Log::error('Get customer sites error: ' . $th->getMessage());
            return ['success' => false, 'error' => $th->getMessage()];
        }
    }

    public function attachSite(int $customerProfileId, int $siteId)
    {
        try {
            $site = Site::findOrFail($siteId);
            $site->update(['customer_profile_id' => $customerProfileId]);

            return [
                'success' => true,
                'data' => new SiteResource($site)
            ];
        } catch (\Throwable $th) {
            Log::error('Attach site to customer error: ' . $th->getMessage());
            return ['success' => false, 'error' => $th->getMessage()];
        }
    }

    public function detachSite(int $customerProfileId, int $siteId)
    {
        try {
            $site = Site::where(['id' => $siteId, 'customer_profile_id' => $customerProfileId])->firstOrFail();
            $site->update(['customer_profile_id' => null]);

            return [
                'success' => true,
                'data' => []
            ];
        } catch (\Throwable $th) {
            Log::error('Detach site from customer error: ' . $th->getMessage());
            return ['success' => false, 'error' => $th->getMessage()];
        }
    }
}

