<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;
        
        // Get privileges from the relation - use getRelationValue to avoid attribute conflicts
        $privilegesCollection = $this->getRelationValue('privileges');
        $compliancesCollection = $this->getRelationValue('compliances');
        
        // If relations are not loaded, load them
        if ($privilegesCollection === null) {
            $privilegesCollection = $this->privileges()->get();
        }
        if ($compliancesCollection === null) {
            $compliancesCollection = $this->compliances()->with('compliance')->get();
        }

        // Expand each privilege item/levels pair into separate entries
        $expandedPrivileges = [];
        foreach ($privilegesCollection as $priv) {
            $privData = $priv->privileges ?? [];
            if (is_array($privData)) {
                foreach ($privData as $item => $levels) {
                    $expandedPrivileges[] = [
                        'item' => $item,
                        'levels' => is_array($levels) ? array_values($levels) : [],
                    ];
                }
            }
        }

        return [
            'profile_id' => $this->id,
            'user_id' => $user?->id,
            'role' => $user?->role,
            'first_name' => $user?->first_name,
            'last_name' => $user?->last_name,
            'email' => $user?->email,
            'status' => $user?->status,
            'image' => $user?->image ? secure_asset('public/storage/' . $user->image) : null,
            'preferred_first_name' => $this->preferred_first_name,
            'preferred_last_name' => $this->preferred_last_name,
            'sia_badge_number' => $this->sia_badge_number,
            'contact_number' => $this->contact_number,
            'emergency_contact' => $this->emergency_contact,
            'gender' => $this->gender,
            'bank_details' => $this->bank_details,
            'tax_number' => $this->tax_number,
            'default_pay_group_id' => $this->default_pay_group_id,
            'email_verified_at' => $user?->email_verified_at,
            'privileges' => $expandedPrivileges,
            'compliances' => $compliancesCollection->sortByDesc('id')->values()->map(function ($c) {
                return [
                    'id' => $c->id,
                    'type' => $c->compliance?->name ?? null,
                    'compliance_id' => $c->compliance_id,
                    'start_date' => $c->start_date,
                    'end_date' => $c->end_date,
                    'files' => $c->files ?? [],
                ];
            })->toArray(),
        ];
    }
}
