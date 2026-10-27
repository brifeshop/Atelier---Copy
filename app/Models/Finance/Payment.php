<?php

namespace App\Models\Finance;

use App\Models\Master\Customer;
use App\Models\Master\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'payment_number', 'direction', 'payment_date', 'method',
        'cash_account_id', 'customer_id', 'supplier_id',
        'total_amount', 'reference_number', 'notes', 'status',
        'journal_id', 'created_by', 'posted_at',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'posted_at'    => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_account_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}