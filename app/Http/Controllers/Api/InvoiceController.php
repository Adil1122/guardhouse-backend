<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Models\Invoice;
use App\Services\InvoiceService;

class InvoiceController extends BaseController
{
    protected $service;
    protected $filterKey = null;

    public function __construct(InvoiceService $service)
    {
        parent::__construct(new Invoice());
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $invoice = new Invoice();
        $validatedData = $request->validate($invoice->getValidationRules('store'));

        $result = $this->service->createInvoice($validatedData);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json($result, 201);
    }
    
    public function update(Request $request, $id)
    {
        $invoice = new Invoice();
        $validatedData = $request->validate($invoice->getValidationRules('update'));

        $result = $this->service->updateInvoice($validatedData, $id);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json($result, 200);
    }

    public function complete(Request $request, $id)
    {
        $request->merge(['id' => $id]);
        $validatedData = $request->validate([
            'id' => ['required', 'integer', 'exists:invoices,id']
        ]);

        $result = $this->service->completeInvoice($validatedData['id']);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json($result, 200);
    }
}
