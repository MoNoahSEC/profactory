<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\Setting;
use App\Models\Treasury;
use Illuminate\Support\Facades\DB;

class CashLedgerService
{
    /**
     * Get the balance of a specific treasury.
     */
    public function getBalance(int $treasuryId): float
    {
        $treasury = Treasury::find($treasuryId);
        return $treasury ? (float) $treasury->current_balance : 0.0;
    }

    /**
     * Set the opening balance for a specific treasury.
     */
    public function setOpeningBalance(int $treasuryId, float $amount, string $note = 'تعديل الرصيد الافتتاحي'): void
    {
        DB::transaction(function () use ($treasuryId, $amount, $note) {
            $treasury = Treasury::findOrFail($treasuryId);
            $treasury->update(['current_balance' => $amount]);

            CashTransaction::create([
                'treasury_id' => $treasuryId,
                'type' => 'adjustment',
                'amount' => $amount,
                'balance_after' => $amount,
                'description' => $note,
                'transaction_date' => now()->toDateString(),
            ]);
        });
    }

    /**
     * Record a transaction in a specific treasury.
     * If no treasury_id is provided, defaults to the primary treasury.
     */
    public function record(string $type, float $amount, string $description, $reference = null, ?string $date = null, ?int $treasuryId = null): CashTransaction
    {
        // Fallback to default treasury if none is specified
        if (!$treasuryId) {
            $defaultTreasury = Treasury::defaultTreasury();
            if (!$defaultTreasury) {
                // Failsafe: Create a default treasury if it doesn't exist
                $defaultTreasury = Treasury::create([
                    'name' => 'الخزينة الرئيسية (نقدية)',
                    'type' => 'cash',
                    'is_default' => true
                ]);
            }
            $treasuryId = $defaultTreasury->id;
        }

        return DB::transaction(function () use ($treasuryId, $type, $amount, $description, $reference, $date) {
            // Lock the specific treasury for update
            $treasury = Treasury::where('id', $treasuryId)->lockForUpdate()->firstOrFail();
            $currentBalance = (float) $treasury->current_balance;
            
            $outflows = ['expense', 'salary', 'worker_advance', 'supplier_payment', 'external_debt_out', 'installment_out', 'payment_reversal', 'deposit_reversal'];
            $inflows = ['deposit', 'payment', 'order_deposit', 'external_debt_in', 'installment_in', 'expense_reversal', 'salary_reversal', 'advance_reversal', 'supplier_payment_reversal', 'debt_out_reversal'];
            
            $isOutflow = in_array($type, $outflows, true);
            $isInflow = in_array($type, $inflows, true);
            
            if ($isOutflow) {
                $signedAmount = -abs($amount);
            } elseif ($isInflow) {
                $signedAmount = abs($amount);
            } else {
                $signedAmount = $amount; // keep original sign for adjustments/transfers
            }
            
            $newBalance = $currentBalance + $signedAmount;

            $data = [
                'treasury_id' => $treasuryId,
                'type' => $type,
                'amount' => $signedAmount,
                'balance_after' => $newBalance,
                'description' => $description,
                'transaction_date' => $date ?? now()->toDateString(),
            ];

            if ($reference) {
                $data['reference_type'] = get_class($reference);
                $data['reference_id'] = $reference->id;
            }

            $tx = CashTransaction::create($data);

            // Update treasury current balance
            $treasury->update(['current_balance' => $newBalance]);

            return $tx;
        });
    }

    /**
     * Get summary statistics for a specific treasury (or all if treasuryId is null).
     */
    public function getSummary($filter = 'monthly', ?int $treasuryId = null): array
    {
        $now = now();
        if ($filter === 'daily') {
            $startDate = $now->copy()->toDateString();
            $endDate = $now->copy()->toDateString();
        } elseif ($filter === 'weekly') {
            $startDate = $now->copy()->startOfWeek(\Carbon\Carbon::SATURDAY)->toDateString();
            $endDate = $now->copy()->endOfWeek(\Carbon\Carbon::FRIDAY)->toDateString();
        } elseif ($filter === 'yearly') {
            $startDate = $now->copy()->startOfYear()->toDateString();
            $endDate = $now->copy()->endOfYear()->toDateString();
        } elseif ($filter === 'all') {
            $startDate = '1970-01-01';
            $endDate = '2100-01-01';
        } else {
            // monthly
            $startDate = $now->copy()->startOfMonth()->toDateString();
            $endDate = $now->copy()->endOfMonth()->toDateString();
        }

        $inflows = ['deposit', 'payment', 'order_deposit', 'external_debt_in', 'installment_in'];
        $outflows = ['expense', 'salary', 'worker_advance', 'supplier_payment', 'external_debt_out', 'installment_out'];
        $inflowReversals = ['payment_reversal', 'deposit_reversal'];
        $outflowReversals = ['expense_reversal', 'salary_reversal', 'advance_reversal', 'supplier_payment_reversal', 'debt_out_reversal'];

        $query = CashTransaction::whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        if ($treasuryId) {
            $query->where('treasury_id', $treasuryId);
        }

        $baseQuery = clone $query;
        $totalIncome = (clone $baseQuery)->whereIn('type', $inflows)->sum('amount');
        $totalIncomeReversals = abs((clone $baseQuery)->whereIn('type', $inflowReversals)->sum('amount')); 
        
        $totalExpenses = abs((clone $baseQuery)->whereIn('type', $outflows)->sum('amount'));
        $totalExpenseReversals = (clone $baseQuery)->whereIn('type', $outflowReversals)->sum('amount'); 

        $balance = $treasuryId ? $this->getBalance($treasuryId) : Treasury::sum('current_balance');

        return [
            'balance' => $balance,
            'period_income' => max(0, $totalIncome - $totalIncomeReversals),
            'period_expenses' => max(0, $totalExpenses - $totalExpenseReversals),
            'period_label' => $filter === 'daily' ? 'اليوم' : ($filter === 'weekly' ? 'الأسبوع' : ($filter === 'yearly' ? 'العام' : ($filter === 'all' ? 'الكلي' : 'الشهر')))
        ];
    }

    /**
     * Recalculate ledger for a specific treasury.
     */
    public function recalculateLedger(?int $treasuryId = null): void
    {
        DB::transaction(function () use ($treasuryId) {
            $treasuries = $treasuryId ? Treasury::where('id', $treasuryId)->get() : Treasury::all();

            foreach ($treasuries as $treasury) {
                $transactions = CashTransaction::where('treasury_id', $treasury->id)->orderBy('id')->get();
                $runningBalance = (float) $treasury->initial_balance;

                foreach ($transactions as $tx) {
                    $outflows = ['expense', 'salary', 'worker_advance', 'supplier_payment', 'external_debt_out', 'installment_out', 'payment_reversal', 'deposit_reversal'];
                    $inflows = ['deposit', 'payment', 'order_deposit', 'external_debt_in', 'installment_in', 'expense_reversal', 'salary_reversal', 'advance_reversal', 'supplier_payment_reversal', 'debt_out_reversal'];
                    
                    $isOutflow = in_array($tx->type, $outflows, true);
                    $isInflow = in_array($tx->type, $inflows, true);
                    
                    if ($isOutflow) {
                        $signedAmount = -abs($tx->amount);
                    } elseif ($isInflow) {
                        $signedAmount = abs($tx->amount);
                    } else {
                        $signedAmount = $tx->amount;
                    }

                    if ($tx->type === 'adjustment') {
                        $runningBalance = $tx->amount;
                        $tx->balance_after = $runningBalance;
                    } else {
                        $runningBalance += $signedAmount;
                        $tx->amount = $signedAmount;
                        $tx->balance_after = $runningBalance;
                    }
                    
                    if ($tx->isDirty()) {
                        $tx->save();
                    }
                }
                
                $treasury->update(['current_balance' => $runningBalance]);
            }
        });
    }

    /**
     * Transfer money between two treasuries.
     */
    public function transfer(int $fromId, int $toId, float $amount, string $note, ?string $date = null): void
    {
        DB::transaction(function () use ($fromId, $toId, $amount, $note, $date) {
            $this->record('transfer_out', -$amount, "تحويل صادر: $note", null, $date, $fromId);
            $this->record('transfer_in', $amount, "تحويل وارد: $note", null, $date, $toId);
        });
    }
}
