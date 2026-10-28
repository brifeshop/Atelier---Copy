<?php

namespace App\Models\Purchasing;

use App\Models\Master\Material;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcInspectionItem extends Model
{
    protected $fillable = [
        'qc_inspection_id', 'goods_receipt_item_id', 'material_id',
        'qty_received', 'qty_ok', 'qty_reject', 'reject_reason', 'notes',
    ];

    protected $casts = [
        'qty_received' => 'decimal:2',
        'qty_ok'       => 'decimal:2',
        'qty_reject'   => 'decimal:2',
    ];

    public function qcInspection(): BelongsTo
    {
        return $this->belongsTo(QcInspection::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}