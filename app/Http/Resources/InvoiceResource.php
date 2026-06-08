<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customerProfile = null;
        $customerInvoiceProfileData = null;

        if ($this->customerInvoiceProfile) {
            $customerProfileRelation = $this->customerInvoiceProfile?->customerProfile;
            
            if ($customerProfileRelation) {
                $user = $customerProfileRelation?->user;
                $customerProfile = [
                    'user_id' => $user?->id ?? null,
                    'customer_profile_id' => $customerProfileRelation?->id,
                    'first_name' => $user?->first_name ?? '',
                    'last_name' => $user?->last_name ?? '',
                    'email' => $user?->email ?? '',
                ];
            }

            $customerInvoiceProfileData = [
                'id' => $this->customerInvoiceProfile->id,
                'contact_first_name' => $this->customerInvoiceProfile->contact_first_name,
                'contact_last_name' => $this->customerInvoiceProfile->contact_last_name,
                'email' => $this->customerInvoiceProfile->email,
                'contact_number' => $this->customerInvoiceProfile->contact_number,
                'address' => $this->customerInvoiceProfile->address,
                'export_invoice_type' => $this->customerInvoiceProfile->export_invoice_type,
                'po_number_prefix' => $this->customerInvoiceProfile->po_number_prefix,
                'company_name' => $this->customerInvoiceProfile->company_name,
                'terms' => $this->customerInvoiceProfile->terms,
            ];
        }

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'items' => InvoiceItemResource::collection($this->invoiceItems),
            'customer' => $customerProfile,
            'customer_invoice_profile' => $customerInvoiceProfileData,
            'tax' => $this->tax,
            'sub_total' => $this->sub_total,
            'grand_total' => $this->grand_total,
            'paid_amount' => $this->paid_amount,
            'due_date' => $this->due_date ? date('d/m/Y', strtotime($this->due_date)) : null,
            'status' => $this->status,
            'notes' => $this->notes,
            'payment_status' => $this->payment_status,
            'payment_status_note' => $this->payment_status_note,
            'created_at' => $this->created_at ? date('d/m/Y h:i', strtotime($this->created_at)) : null,
        ];
    }
}
