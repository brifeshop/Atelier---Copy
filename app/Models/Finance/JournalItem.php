<?php

namespace App\Models\Finance;

use App\Models\Master\Customer;
use App\Models\Master\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalItem extends Model
{
    protected $fillable = [
        'journal_id', 'account_id', 'debit', 'credit', 'memo',
        'customer_id', 'supplier_id',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}