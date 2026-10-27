<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'code', 'name', 'type', 'normal_balance', 'parent_id',
        'level', 'is_header', 'is_active', 'description',
    ];

    protected $casts = [
        'is_header' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public static function byCode(string $code): self
    {
        return self::where('code', $code)->where('is_active', true)->firstOrFail();
    }
}