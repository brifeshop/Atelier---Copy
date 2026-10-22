<?php

namespace App\Models\Sales;

use App\Models\Master\Product;
use App\Models\Warehouse\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id',
        'sales_order_item_id',
        'product_id',
        'location_id',
        'qty_ordered',
        'qty_delivered',
        'qty_shipped',
        'notes',
    ];

    protected $casts = [
        'qty_ordered'   => 'decimal:2',
        'qty_delivered' => 'decimal:2',
        'qty_shipped'   => 'decimal:2',
    ];

    // ========== RELASI ==========

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'sales_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // ========== HELPERS ==========

    public function getRemainingQtyAttribute(): float
    {
        return (float) ($this->qty_delivered - $this->qty_shipped);
    }

    public function isFullyShipped(): bool
    {
        return $this->qty_shipped >= $this->qty_delivered;
    }
}