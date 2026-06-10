<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;

class CustomerInvoiceController extends Controller
{
    // GET customers/{customerId}/invoices
    public function index(Request $request, $customerId)
    {
        $profileIds = \App\Models\CustomerInvoiceProfile::where('customer_profile_id', $customerId)->pluck('id');
        $invoices = Invoice::whereIn('customer_invoice_profile_id', $profileIds)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($invoices, 200);
    }

    // POST customers/{customerId}/invoices
    public function store(Request $request, $customerId)
    {
        $request->validate([
            'customer_invoice_profile_id' => 'nullable|exists:customer_invoice_profiles,id',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::create([
            'customer_invoice_profile_id' => $request->customer_invoice_profile_id,
            'reference_number' => $request->input('reference_number', 'INV-' . time()),
            'due_date' => $request->due_date,
            'notes' => $request->notes,
            'sub_total' => $request->input('sub_total', 0),
            'grand_total' => $request->input('grand_total', 0),
        ]);

        return response()->json($invoice, 201);
    }

    // PATCH customers/{customerId}/invoices/{id}
    public function update(Request $request, $customerId, $id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $invoice->update($request->only(['due_date', 'status', 'notes', 'paid_amount', 'payment_status']));
        return response()->json($invoice, 200);
    }

    // DELETE customers/{customerId}/invoices/{id}
    public function destroy(Request $request, $customerId, $id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted'], 200);
    }
}
