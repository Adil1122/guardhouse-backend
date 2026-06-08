<?php

namespace App\Services;

use App\Models\CustomerContact;
use Illuminate\Support\Facades\Log;

class CustomerContactService
{
    public function createContact(array $data)
    {
        try {
            $contact = CustomerContact::create($data);
            return [
                'success' => true,
                'data' => $contact
            ];
        } catch (\Throwable $th) {
            Log::error('Customer contact create error: ' . $th->getMessage());
            return ['success' => false, 'error' => $th->getMessage()];
        }
    }

    public function updateContact(array $data, int $id)
    {
        try {
            $contact = CustomerContact::findOrFail($id);
            $contact->update($data);
            return [
                'success' => true,
                'data' => $contact
            ];
        } catch (\Throwable $th) {
            Log::error('Customer contact update error: ' . $th->getMessage());
            return ['success' => false, 'error' => $th->getMessage()];
        }
    }

    public function deleteContact(int $id)
    {
        try {
            $contact = CustomerContact::findOrFail($id);
            $contact->delete();
            return [
                'success' => true,
                'data' => []
            ];
        } catch (\Throwable $th) {
            Log::error('Customer contact delete error: ' . $th->getMessage());
            return ['success' => false, 'error' => $th->getMessage()];
        }
    }
}
