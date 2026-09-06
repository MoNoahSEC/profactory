<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DebtInstallment extends Model
{
    protected $fillable = [
        'external_debt_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    // ── Relationships ──────────────────────────────

    public function debt(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExternalDebt::class, 'external_debt_id');
    }

    // ── Scopes ─────────────────────────────────────

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
                     ->where('due_date', '<', Carbon::today());
    }

    public function scopeUpcoming($query, int $days = 30)
    {
        return $query->where('status', '!=', 'paid')
                     ->whereBetween('due_date', [Carbon::today(), Carbon::today()->addDays($days)]);
    }

    public function scopePending($query)
    {
        return $query->where('status', '!=', 'paid');
    }

    // ── Helpers ────────────────────────────────────

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid' && $this->due_date < Carbon::today();
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->status === 'paid') return 0;
        return (int) Carbon::today()->diffInDays($this->due_date, false);
    }
}
