<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerInvoiceProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customerProfile = $this->customerProfile;

        return [
            'id' => $this->id,
            'customer' => [
                'id' => $customerProfile->user->id,
                'profile_id' => $customerProfile->id,
                'first_name' => $customerProfile->user->first_name,
                'last_name' => $customerProfile->user->last_name,
                'email' => $customerProfile->user->email,
            ],
            'company_name' => $this->company_name,
            'contact_first_name' => $this->contact_first_name,
            'contact_last_name' => $this->contact_last_name,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'po_number_prefix' => $this->po_number_prefix,
            'tax_rate' => $this->tax_rate,
            'export_invoice_type' => $this->export_invoice_type,
            'address' => $this->address,
            'terms' => $this->terms,
            'created_at' => date('Y-m-d H:i A', strtotime($this->created_at)),
        ];
    }
}
