<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'address', 'type', 'notes', 'deposit_balance'
    ];

    protected $casts = [
        'deposit_balance' => 'decimal:2',
    ];

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function deposits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerDeposit::class)->orderByDesc('date');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        // Use DB aggregate to avoid loading thousands of invoices into memory
        $unpaidInvoices = (float) $this->invoices()->sum('remaining_amount');
        
        // Positive outstanding balance means they OWE money.
        // deposit_balance: Positive = credit, Negative = debt.
        return round($unpaidInvoices - (float)$this->deposit_balance, 2);
    }

    public function getStatement()
    {
        $allEntries = collect();

        foreach($this->deposits as $dep) {
            if ($dep->type === 'applied') {
                continue;
            }
            $isDebt = $dep->type === 'debt_adjustment';
            $allEntries->push([
                'id'     => $dep->id,
                'source' => 'deposit',
                'date'   => $dep->date,
                'label'  => $dep->description ?? 'مبلغ مستلم / دفعة مقدمة',
                'credit' => $isDebt ? 0 : (float)$dep->amount,
                'debit'  => $isDebt ? (float)$dep->amount : 0,
                'raw_type' => $dep->type,
                'badge'  => $isDebt
                    ? '<span class="badge bg-danger bg-opacity-25 text-danger border border-danger rounded-pill">دين قديم</span>'
                    : '<span class="badge bg-success bg-opacity-25 text-success border border-success rounded-pill">عربون/دفعة</span>',
            ]);
        }

        foreach($this->invoices as $inv) {
            $allEntries->push([
                'id'     => $inv->id,
                'source' => 'invoice',
                'date'   => $inv->invoice_date,
                'label'  => 'فاتورة #' . $inv->invoice_number,
                'credit' => 0,
                'debit'  => (float)$inv->total_amount,
                'badge'  => '<span class="badge bg-warning bg-opacity-25 text-warning border border-warning rounded-pill">فاتورة</span>',
            ]);
            foreach($inv->payments as $pay) {
                $allEntries->push([
                    'id'     => $pay->id,
                    'source' => 'payment',
                    'date'   => $pay->payment_date,
                    'label'  => 'تحصيل فاتورة #' . $inv->invoice_number,
                    'credit' => (float)$pay->amount,
                    'debit'  => 0,
                    'badge'  => '<span class="badge bg-info bg-opacity-25 text-info border border-info rounded-pill">تحصيل</span>',
                ]);
            }
        }

        return $allEntries->sortBy('date');
    }
}
