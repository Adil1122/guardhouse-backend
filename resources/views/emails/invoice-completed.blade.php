@component('mail::message')
# Invoice Created

Hello {{ $customerName }},

We are pleased to inform you that your invoice **#{{ $invoice->reference_number }}** has been **created**.

@component('mail::table')
| Item | Type | Amount |
|------|------|-------|
@foreach($invoice->invoiceItems as $item)
| {{ $item->reference_type }} #{{ $item->reference_id }} | {{ ucfirst($item->reference_type) }} | ${{ number_format($item->reference->service_total_amount ?? 0, 2) }} |
@endforeach
@endcomponent

**Subtotal:** ${{ number_format($invoice->sub_total, 2) }} 
**Grand Total:** ${{ number_format($invoice->grand_total, 2) }}  

Please review the invoice at your earliest convenience.  
If you have any questions, feel free to contact us.

Thank you for your business!  

Regards,  
**{{ config('app.name') }} Team**
@endcomponent
