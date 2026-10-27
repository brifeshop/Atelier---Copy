<?php

namespace App\Models\Finance;

use App\Models\Master\Customer;
use App\Models\Sales\SalesInvoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArInvoice extends Model
{
    protected $fillable = [
        'ar_number', 'sales_invoice_id', 'customer_id', 'invoice_date',
        'due_date', 'total_amount', 'paid_amount', 'status',
        'journal_id', 'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
    ];

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'allocatable_id')
            ->where('allocatable_type', 'ar');
    }

    public function getOutstandingAttribute(): float
    {
        return (float) ($this->total_amount - $this->paid_amount);
    }
}