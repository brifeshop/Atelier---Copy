<?php

namespace App\Models\Sales;

use App\Models\User;
use App\Models\Master\Customer;
use App\Models\Warehouse\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'do_number',
        'sales_order_id',
        'customer_id',
        'warehouse_id',
        'status',
        'delivery_date',
        'shipping_address',
        'vehicle_number',
        'driver_name',
        'notes',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'created_by',
        'shipped_by',
        'delivered_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'shipped_at'    => 'datetime',
        'delivered_at'  => 'datetime',
        'cancelled_at'  => 'datetime',
    ];

    // ========== RELASI ==========

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class, 'delivery_order_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(DeliveryOrderStatusLog::class, 'delivery_order_id')
            ->orderBy('created_at', 'desc');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    // ========== SCOPES ==========

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeOverdue($query)
    {
        return $query->where('delivery_date', '<', now()->toDateString())
            ->whereNotIn('status', ['delivered', 'cancelled']);
    }

    // ========== HELPERS ==========

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'ready']);
    }

    public function canBeShipped(): bool
    {
        return $this->status === 'ready';
    }

    public function canBeDelivered(): bool
    {
        return $this->status === 'shipped';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'ready', 'shipped']);
    }

    public function getTotalQtyAttribute(): float
    {
        return (float) $this->items->sum('qty_delivered');
    }

    public function getTotalShippedQtyAttribute(): float
    {
        return (float) $this->items->sum('qty_shipped');
    }
    
        public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'delivery_order_id');
    }
        public function returns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalesReturn::class, 'delivery_order_id');
    }
}