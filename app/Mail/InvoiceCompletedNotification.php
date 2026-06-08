<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceCompletedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function build()
    {
        return $this->subject("Invoice #{$this->invoice->reference_number} Created")
            ->markdown('emails.invoice-completed')
            ->with([
                'invoice' => $this->invoice,
                'customerName' => $this->invoice->customer->name ?? 'Valued Customer',
            ]);
    }
}
