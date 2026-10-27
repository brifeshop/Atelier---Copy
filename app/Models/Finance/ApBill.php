<?php

namespace App\Models\Finance;

use App\Models\Master\Supplier;
use App\Models\Purchasing\GoodsReceipt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApBill extends Model
{
    protected $fillable = [
        'ap_number', 'goods_receipt_id', 'supplier_id', 'bill_date',
        'due_date', 'total_amount', 'paid_amount', 'status',
        'journal_id', 'created_by',
    ];

    protected $casts = [
        'bill_date'    => 'date',
        'due_date'     => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'allocatable_id')
            ->where('allocatable_type', 'ap');
    }

    public function getOutstandingAttribute(): float
    {
        return (float) ($this->total_amount - $this->paid_amount);
    }
}