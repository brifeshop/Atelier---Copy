<?php

namespace App\Models\Purchasing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QcInspection extends Model
{
    protected $fillable = [
        'qc_number', 'goods_receipt_id', 'inspection_date', 'status',
        'inspection_method', 'sample_size', 'inspector_id', 'notes',
        'completed_at', 'created_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'completed_at'    => 'datetime',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QcInspectionItem::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}