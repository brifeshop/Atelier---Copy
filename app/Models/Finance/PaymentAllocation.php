<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    protected $fillable = ['payment_id', 'allocatable_type', 'allocatable_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Karena kita pakai enum 'ar'/'ap' (bukan full class name),
     * relasi ini kita definisikan manual.
     */
    public function arInvoice(): BelongsTo
    {
        return $this->belongsTo(ArInvoice::class, 'allocatable_id');
    }

    public function apBill(): BelongsTo
    {
        return $this->belongsTo(ApBill::class, 'allocatable_id');
    }

    /**
     * Accessor untuk ambil record AR atau AP sesuai tipe.
     */
    public function getAllocatableAttribute()
    {
        return match ($this->allocatable_type) {
            'ar'    => $this->arInvoice,
            'ap'    => $this->apBill,
            default => null,
        };
    }
}