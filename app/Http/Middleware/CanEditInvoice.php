<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Invoice;
use App\Models\CustomerInvoiceProfile;

class CanEditInvoice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $invoiceId = $request->route('id');

        $invoice = Invoice::with('customerInvoiceProfile.customerProfile')->find($invoiceId);

        if (!$invoice) {
            abort(404, 'Invoice not found.');
        }

        // Allow editing regardless of status for now as requested
        // if ($invoice->status === 'approved' || $invoice->status === 'complete') {
        //     abort(403, 'Approved invoices cannot be edited.');
        // }

        $customerProfileId = optional($invoice->customerInvoiceProfile)->customer_profile_id;
        $newInvoiceProfileId = $request->input('customer_invoice_profile_id');

        if ($newInvoiceProfileId) {

            $isValid = CustomerInvoiceProfile::where('id', $newInvoiceProfileId)->where('customer_profile_id', $customerProfileId)->exists();
            if (!$isValid) {
                abort(403, 'Invalid customer invoice profile for this invoice.');
            }
        }

        return $next($request);
    }
}
