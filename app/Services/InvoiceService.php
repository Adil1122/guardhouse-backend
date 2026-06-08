<?php

namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Models\Invoice;
use App\Models\Timesheet;
use App\Models\ManualBillableItem;
use App\Http\Resources\InvoiceResource;
use App\Events\InvoiceCreated;
use App\Events\InvoiceUpdated;
use App\Mail\InvoiceCompletedNotification;
use Illuminate\Support\Facades\Mail;

class InvoiceService
{
    public function createInvoice($data)
    {
        try {
            $invoice = DB::transaction(function () use ($data) {
                
                $totals = $this->calculateTotals($data['invoice_items'], $data['tax_percentage'] ?? null);
                $invoiceData = [
                    'reference_number' => $data['reference_number'],
                    'tax' => $totals['tax'] ?? null,
                    'sub_total' => $totals['sub_total'],
                    'grand_total' => $totals['grand_total'],
                    'due_date' => $data['due_date'],
                    'notes' => $data['notes'] ?? null
                ];

                // Only set customer_invoice_profile_id if it's provided and valid
                if (isset($data['customer_invoice_profile_id'])) {
                    $profileId = $data['customer_invoice_profile_id'];
                    // Verify the profile exists
                    $profile = \App\Models\CustomerInvoiceProfile::find($profileId);
                    if ($profile) {
                        $invoiceData['customer_invoice_profile_id'] = $profileId;
                    }
                }

                $invoice = Invoice::create($invoiceData);
    
                $this->syncInvoiceItems('create', $invoice, $data['invoice_items']);
                return $invoice;
            });
    
            return [
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => new InvoiceResource($invoice)
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Failed to create invoice',
                'error' => $th->getMessage()
            ];
        }
    }
    
    public function updateInvoice($data, $id)
    {
        try {
            $invoice = DB::transaction(function () use ($data, $id) {

                $invoice = Invoice::findOrFail($id);
                $totals = isset($data['invoice_items']) ? $this->calculateTotals($data['invoice_items'], $data['tax_percentage'] ?? null) : null;

                $invoice->update([
                    'reference_number' => $data['reference_number'] ?? $invoice->reference_number,
                    'customer_invoice_profile_id' => $data['customer_invoice_profile_id'] ?? $invoice->customer_invoice_profile_id,
                    'tax' => $totals ? $totals['tax'] : $invoice->tax,
                    'sub_total' => $totals ? $totals['sub_total'] : $invoice->sub_total,
                    'grand_total' => $totals ? $totals['grand_total'] : $invoice->grand_total,
                    'paid_amount' => $data['paid_amount'] ?? $invoice->paid_amount,
                    'due_date' => $data['due_date'] ?? $invoice->due_date,
                    'status' => $data['status'] ?? $invoice->status,
                    'notes' => $data['notes'] ?? $invoice->notes,
                    'payment_status' => $data['payment_status'] ?? $invoice->payment_status,
                    'payment_status_note' => $data['payment_status_note'] ?? $invoice->payment_status_note,
                ]);

                if (isset($data['invoice_items'])) {
                    $this->syncInvoiceItems('update', $invoice, $data['invoice_items']);
                }

                return $invoice;
            });

            return [
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => new InvoiceResource($invoice)
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Failed to update invoice',
                'error' => $th->getMessage()
            ];
        }
    }

    public function syncInvoiceItems($mode, $invoice, $items)
    {
        if ($mode == 'update') {
            $previousItems = $invoice->invoiceItems()->get();
            
            $removedTimesheetIds = $previousItems->where('reference_type', 'timesheet')->pluck('reference_id')->diff(collect($items)->pluck('reference_id'));
            if ($removedTimesheetIds->isNotEmpty()) {
                Timesheet::whereIn('id', $removedTimesheetIds)->update(['status' => 'approved']);
            }
        }

        $invoice->invoiceItems()->delete();

        foreach ($items as $item) {

            if ($item['reference_type'] === 'timesheet') {
                $reference = Timesheet::where('id', $item['reference_id'])->whereIn('status', ['approved', 'invoiced'])->first();
                if ($reference) {
                    $reference->update(['status' => 'invoiced']);
                }
            } else if ($item['reference_type'] === 'manual-billable') {
                $reference = ManualBillableItem::create([
                    'user_id' => auth()->id(),
                    'service' => ['name' => $item['name'], 'rate' => $item['rate'], 'units' => $item['units']],
                    'date' => date('Y-m-d', strtotime($item['date'])),
                    'total_amount' => $item['total_amount'],
                    'note' => $item['note'] ?? null,
                ]);
            }

            if ($reference) {
                $invoice->invoiceItems()->create([
                    'reference_type' => $item['reference_type'],
                    'reference_id' => $reference->id,
                ]);
            }
        }
    }
    
    public function completeInvoice($id)
    {
        try {
            $invoice = DB::transaction(function () use ($id) {

                $invoice = Invoice::where(['id' => $id, 'status' => 'draft'])->first();
                if (!$invoice) {
                    throw new \Exception('Invoice not found');
                }

                $invoice->update(['status' => 'complete']);

                $customerProfileEmail = $invoice->customerInvoiceProfile->email ?? null;
                $customerEmail = $invoice->customer->email ?? null;

                if ($customerProfileEmail) {
                    $cc = ($customerEmail && $customerEmail !== $customerProfileEmail) ? [$customerEmail] : [];
                    Mail::to($customerProfileEmail)->cc($cc)->send(new InvoiceCompletedNotification($invoice));
                }
                
                return $invoice;
            });

            return [
                'success' => true,
                'message' => 'Invoice completed successfully',
                'data' => $invoice
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Failed to update invoice',
                'error' => $th->getMessage()
            ];
        }
    }

    private function calculateTotals(array $items, ?int $taxPercentage = 0): array
    {
        $subTotal = 0;
        $tax = ['percentage' => $taxPercentage, 'amount' => 0];

        foreach ($items as $item) {

            if ($item['reference_type'] === 'timesheet') {
                $reference = Timesheet::where('id', $item['reference_id'])->whereIn('status', ['approved', 'invoiced'])->first();
            } elseif ($item['reference_type'] === 'manual-billable') {
                $reference = $item;
            } else {
                $reference = null;
            }

            if (!$reference) {
                continue;
            }

            $subTotal += $item['reference_type'] === 'manual-billable' ? $reference['total_amount'] : $reference->service_total_amount;
        }

        if ($tax['percentage'] > 0) {
            $tax['amount'] = $subTotal * ($tax['percentage'] / 100);
        }

        $grandTotal = $subTotal + $tax['amount'];

        return [
            'sub_total' => $subTotal,
            'tax' => $tax,
            'grand_total' => $grandTotal,
        ];
    }
}
