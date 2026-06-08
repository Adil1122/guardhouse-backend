<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\InvoiceResource;
use App\Rules\InvoiceItemRule;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'customer_invoice_profile_id',
        'tax',
        'sub_total',
        'grand_total',
        'paid_amount',
        'due_date',
        'status',
        'notes',
        'payment_status',
        'payment_status_note',
    ];

    protected $casts = [
        'tax' => 'array',
        'sub_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function getValidationRules($action)
    {
        if ($action === 'store') {
            return [
                'reference_number' => 'required|string|max:30',
                'customer_invoice_profile_id' => 'nullable|integer',
                'tax' => 'nullable|array',
                'tax_percentage' => 'nullable|numeric',
                'due_date' => 'required|date',
                'notes' => 'nullable|string',
                'invoice_items' => 'required|array|min:1',
                'invoice_items.*.reference_type' => 'nullable|in:timesheet,manual-billable',
                'invoice_items.*' => [new InvoiceItemRule()],
            ];
        } else if ($action === 'update') {
            return [
                'reference_number' => 'nullable|string|max:30',
                'customer_invoice_profile_id' => 'nullable|integer',
                'tax' => 'nullable|array',
                'tax_percentage' => 'nullable|numeric',
                'paid_amount' => 'nullable|numeric',
                'due_date' => 'nullable|date',
                'notes' => 'nullable|string',
                'payment_status' => 'nullable|in:pending,partially-paid,paid,failed,retry-failed',
                'payment_status_note' => 'nullable|string',
                'invoice_items' => 'nullable|array|min:1',
                'invoice_items.*.reference_type' => 'nullable|in:timesheet,manual-billable',
                'invoice_items.*' => [new InvoiceItemRule()],
            ];
        }

        return [];
    }

    public static function getResource()
    {
        return InvoiceResource::class;
    }

    public static function scopeApplyFilter($query, $filter, $filterId)
    {
        if (empty($filter) || $filter === 'all') {
            return $query;
        }

        $query = $query->whereHas('customerInvoiceProfile', function ($q) use ($filter) {
            $q->where('customer_profile_id', $filter);
        });
        
        return $query;
    }

    public function customerInvoiceProfile()
    {
        return $this->belongsTo(CustomerInvoiceProfile::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
