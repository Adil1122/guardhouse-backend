<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ManualBillableItem;
use App\Models\Timesheet;

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resource_type' => $this->reference_type,
            'resource' => $this->reference_type === 'timesheet' ? new TimesheetResource(Timesheet::find($this->reference_id)) : new ManualBillableItemResource(ManualBillableItem::find($this->reference_id)),
            'created_at' => date('d/m/Y h:i', strtotime($this->created_at)),
        ];
    }
}
