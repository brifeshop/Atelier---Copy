<?php

namespace App\Models\Sales;

use App\Models\Master\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $table = 'sales_order_items';

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'qty',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'hpp_actual',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'hpp_actual' => 'decimal:2',
    ];

    /**
     * Relasi ke Sales Order
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Relasi ke Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Hitung subtotal + diskon otomatis
     */
    public function calculateTotals(): void
    {
        $gross = $this->qty * $this->unit_price;
        $this->discount_amount = round($gross * $this->discount_percent / 100, 2);
        $this->subtotal = $gross - $this->discount_amount;
    }

    /**
     * Boot: auto-hitung sebelum simpan
     */
    protected static function booted()
    {
        static::saving(function ($item) {
            $item->calculateTotals();
        });
    }
    public function deliveryOrderItems()
        {
            return $this->hasMany(DeliveryOrderItem::class, 'sales_order_item_id');
        }
}