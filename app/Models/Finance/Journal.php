<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Journal extends Model
{
    protected $fillable = [
        'journal_number', 'journal_date', 'reference_type', 'reference_id',
        'reference_number', 'description', 'status', 'total_debit',
        'total_credit', 'created_by', 'posted_at', 'posted_by',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posted_at' => 'datetime',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(JournalItem::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isBalanced(): bool
    {
        return bccomp((string) $this->total_debit, (string) $this->total_credit, 2) === 0;
    }
}