<?php

namespace App\Models\Sales;

use App\Models\Master\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'delivery_order_id',
        'customer_id',
        'return_date',
        'status',
        'reason',
        'total_amount',
        'notes',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'completed_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // ========== RELASI ==========

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class, 'sales_return_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ========== HELPERS ==========

    public function canBeCompleted(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'completed']);
    }

    public function getReasonLabelAttribute(): string
    {
        return match($this->reason) {
            'damaged'    => 'Barang Rusak',
            'wrong_item' => 'Salah Kirim Barang',
            'expired'    => 'Kadaluarsa',
            'other'      => 'Lainnya',
            default      => ucfirst($this->reason),
        };
    }
}