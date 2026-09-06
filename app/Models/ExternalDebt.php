<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalDebt extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_name',
        'type',
        'amount',
        'total_amount',
        'paid_amount',
        'installments_count',
        'debt_date',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'debt_date' => 'date',
    ];

    // ── Relationships ──────────────────────────────

    public function installments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DebtInstallment::class)->orderBy('installment_number');
    }

    // ── Computed Attributes ────────────────────────

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_amount <= 0) return 0;
        return min(100, round(($this->paid_amount / $this->total_amount) * 100, 1));
    }

    public function getPaidInstallmentsCountAttribute(): int
    {
        return $this->installments()->where('status', 'paid')->count();
    }

    public function getNextInstallmentAttribute()
    {
        return $this->installments()
                     ->where('status', '!=', 'paid')
                     ->orderBy('due_date')
                     ->first();
    }

    public function getOverdueInstallmentsCountAttribute(): int
    {
        return $this->installments()
                     ->where('status', '!=', 'paid')
                     ->where('due_date', '<', Carbon::today())
                     ->count();
    }

    // ── Business Logic ────────────────────────────

    /**
     * Record a payment against this debt and recalculate status.
     */
    public function recordPayment(float $amount): void
    {
        $this->increment('paid_amount', $amount);
        $this->refresh();
        $this->recalculateStatus();
    }

    /**
     * Recalculate debt status based on paid amount and installments.
     */
    public function recalculateStatus(): void
    {
        if ($this->paid_amount >= $this->total_amount) {
            $this->update(['status' => 'paid']);
            // Mark any remaining installments as paid
            $this->installments()->where('status', '!=', 'paid')->update([
                'status' => 'paid',
                'paid_date' => Carbon::today(),
            ]);
        } else {
            // Check if overdue based on due_date or installments
            $hasOverdue = $this->installments()
                ->where('status', '!=', 'paid')
                ->where('due_date', '<', Carbon::today())
                ->exists();

            $isOverdueByDate = $this->due_date && $this->due_date < Carbon::today();

            $this->update([
                'status' => ($hasOverdue || $isOverdueByDate) ? 'overdue' : 'pending',
            ]);
        }
    }

    /**
     * Generate installments for this debt.
     */
    public function generateInstallments(int $count, string $startDate, string $interval = 'monthly'): void
    {
        // Delete existing installments
        $this->installments()->delete();

        $installmentAmount = round($this->total_amount / $count, 2);
        $remainder = $this->total_amount - ($installmentAmount * $count);
        $date = Carbon::parse($startDate);

        for ($i = 1; $i <= $count; $i++) {
            $amt = $installmentAmount;
            // Add remainder to last installment to ensure total matches exactly
            if ($i === $count) {
                $amt += $remainder;
            }

            $this->installments()->create([
                'installment_number' => $i,
                'amount' => $amt,
                'due_date' => $date->copy(),
                'status' => 'pending',
            ]);

            // Advance date based on interval
            if ($interval === 'weekly') {
                $date->addWeek();
            } else {
                $date->addMonth();
            }
        }

        $this->update([
            'installments_count' => $count,
            'due_date' => $this->installments()->max('due_date'),
        ]);
    }

    // ── Scopes ────────────────────────────────────

    public function scopeOwedByUs($query)
    {
        return $query->where('type', 'owed_by_us');
    }

    public function scopeOwedToUs($query)
    {
        return $query->where('type', 'owed_to_us');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 'paid');
    }
}
