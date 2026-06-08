<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Timesheet;
use App\Models\ManualBillableItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceItemController extends Controller
{
    public function store(Request $request, $invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        
        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Cannot add items to a non-draft invoice'], 400);
        }

        $validated = $request->validate([
            'reference_type' => 'required|in:timesheet,manual-billable',
            'reference_id' => 'required|integer',
            'name' => 'required_if:reference_type,manual-billable|string|max:255',
            'rate' => 'required_if:reference_type,manual-billable|numeric',
            'units' => 'required_if:reference_type,manual-billable|numeric',
            'total_amount' => 'required_if:reference_type,manual-billable|numeric',
            'date' => 'required_if:reference_type,manual-billable|date',
            'note' => 'nullable|string',
        ]);

        try {
            $result = DB::transaction(function () use ($invoice, $validated) {
                $reference = null;
                
                if ($validated['reference_type'] === 'timesheet') {
                    $timesheet = Timesheet::where('id', $validated['reference_id'])
                        ->where('status', 'approved')
                        ->first();
                    
                    if (!$timesheet) {
                        return ['success' => false, 'error' => 'Timesheet not found or not approved'];
                    }
                    
                    $timesheet->update(['status' => 'invoiced']);
                    $reference = $timesheet;
                } else {
                    $reference = ManualBillableItem::create([
                        'user_id' => auth()->id(),
                        'service' => [
                            'name' => $validated['name'],
                            'rate' => $validated['rate'],
                            'units' => $validated['units'],
                        ],
                        'date' => $validated['date'],
                        'total_amount' => $validated['total_amount'],
                        'note' => $validated['note'] ?? null,
                    ]);
                }

                $invoiceItem = $invoice->invoiceItems()->create([
                    'reference_type' => $validated['reference_type'],
                    'reference_id' => $reference->id,
                ]);

                $this->recalculateInvoiceTotals($invoice);

                return [
                    'success' => true,
                    'message' => 'Item added successfully',
                    'item' => $invoiceItem
                ];
            });

            if (!$result['success']) {
                return response()->json(['message' => $result['error']], 400);
            }

            return response()->json($result, 201);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to add item: ' . $th->getMessage()], 500);
        }
    }

    public function update(Request $request, $invoiceId, $itemId)
    {
        $invoice = Invoice::find($invoiceId);
        
        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Cannot update items of a non-draft invoice'], 400);
        }

        $invoiceItem = InvoiceItem::where('invoice_id', $invoiceId)->find($itemId);
        
        if (!$invoiceItem) {
            return response()->json(['message' => 'Invoice item not found'], 404);
        }

        if ($invoiceItem->reference_type === 'manual-billable') {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'rate' => 'sometimes|numeric',
                'units' => 'sometimes|numeric',
                'total_amount' => 'sometimes|numeric',
                'date' => 'sometimes|date',
                'note' => 'nullable|string',
            ]);

            try {
                $manualItem = ManualBillableItem::find($invoiceItem->reference_id);
                
                if ($manualItem) {
                    $updateData = [];
                    if (isset($validated['name'])) {
                        $service = $manualItem->service;
                        $service['name'] = $validated['name'];
                        $updateData['service'] = $service;
                    }
                    if (isset($validated['rate'])) {
                        $service = $manualItem->service;
                        $service['rate'] = $validated['rate'];
                        $updateData['service'] = $service;
                    }
                    if (isset($validated['units'])) {
                        $service = $manualItem->service;
                        $service['units'] = $validated['units'];
                        $updateData['service'] = $service;
                    }
                    if (isset($validated['date'])) {
                        $updateData['date'] = $validated['date'];
                    }
                    if (array_key_exists('note', $validated)) {
                        $updateData['note'] = $validated['note'];
                    }
                    
                    $manualItem->update($updateData);
                }

                $this->recalculateInvoiceTotals($invoice);

                return response()->json(['message' => 'Item updated successfully'], 200);
            } catch (\Throwable $th) {
                return response()->json(['message' => 'Failed to update item: ' . $th->getMessage()], 500);
            }
        }

        return response()->json(['message' => 'Cannot update timesheet items'], 400);
    }

    public function destroy($invoiceId, $itemId)
    {
        $invoice = Invoice::find($invoiceId);
        
        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Cannot delete items from a non-draft invoice'], 400);
        }

        $invoiceItem = InvoiceItem::where('invoice_id', $invoiceId)->find($itemId);
        
        if (!$invoiceItem) {
            return response()->json(['message' => 'Invoice item not found'], 404);
        }

        try {
            DB::transaction(function () use ($invoice, $invoiceItem) {
                if ($invoiceItem->reference_type === 'timesheet') {
                    Timesheet::where('id', $invoiceItem->reference_id)
                        ->update(['status' => 'approved']);
                } else {
                    ManualBillableItem::where('id', $invoiceItem->reference_id)->delete();
                }

                $invoiceItem->delete();
                $this->recalculateInvoiceTotals($invoice);
            });

            return response()->json(['message' => 'Item deleted successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to delete item: ' . $th->getMessage()], 500);
        }
    }

    private function recalculateInvoiceTotals($invoice)
    {
        $subTotal = 0;
        
        foreach ($invoice->invoiceItems as $item) {
            if ($item->reference_type === 'timesheet') {
                $timesheet = Timesheet::find($item->reference_id);
                if ($timesheet) {
                    $subTotal += $timesheet->service_total_amount ?? 0;
                }
            } else {
                $manualItem = ManualBillableItem::find($item->reference_id);
                if ($manualItem) {
                    $subTotal += $manualItem->total_amount ?? 0;
                }
            }
        }

        $taxPercentage = $invoice->tax['percentage'] ?? 0;
        $taxAmount = $subTotal * ($taxPercentage / 100);
        $grandTotal = $subTotal + $taxAmount;

        $invoice->update([
            'sub_total' => $subTotal,
            'grand_total' => $grandTotal,
        ]);
    }
}
