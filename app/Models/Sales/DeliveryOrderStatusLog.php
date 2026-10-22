<?php

namespace App\Models\Sales;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by',
    ];

    // ========== RELASI ==========

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}