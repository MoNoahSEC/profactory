<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    protected $fillable = [
        'name', 'phone', 'address', 'deposit_balance', 'notes'
    ];

    protected $casts = [
        'deposit_balance' => 'decimal:2',
    ];

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkshopInvoice::class);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkshopTransaction::class)->orderByDesc('date');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        $totals = $this->invoices()->selectRaw('SUM(net_amount) as total, SUM(paid_amount) as paid')->first();
        
        $totalInvoiced = $totals->total ?? 0;
        $totalPaid     = $totals->paid ?? 0;
        
        $unpaidInvoices = $totalInvoiced - $totalPaid;
        
        // Positive outstanding balance means they OWE money.
        // deposit_balance: Positive = credit, Negative = debt.
        return round($unpaidInvoices - (float)$this->deposit_balance, 2);
    }

    public function getStatement()
    {
        $allEntries = collect();

        foreach($this->transactions as $trans) {
            $isDebtUs = in_array($trans->type, ['debt_adjustment', 'old_debt_us']);
            $isDebtThem = $trans->type === 'old_debt_them';
            
            $label = $trans->description;
            if (!$label) {
                if ($trans->type === 'payment') $label = 'دفعة مسددة منا للورشة';
                elseif ($trans->type === 'receipt') $label = 'دفعة مستلمة من الورشة';
                elseif ($trans->type === 'debt_adjustment') $label = 'تسوية دين (على الورشة)';
                elseif ($trans->type === 'old_debt_us') $label = 'حساب قديم (لنا)';
                elseif ($trans->type === 'old_debt_them') $label = 'حساب قديم (علينا)';
            }
            
            // Debit: We paid them (payment) or they owe us (isDebtUs)
            // Credit: They paid us (receipt) or we owe them (isDebtThem)
            $allEntries->push([
                'id'       => 'trans_' . $trans->id,
                'source'   => 'transaction',
                'date'     => $trans->date,
                'label'    => $label,
                'credit'   => ($trans->type === 'receipt' || $isDebtThem) ? (float)$trans->amount : 0,
                'debit'    => ($trans->type === 'payment' || $isDebtUs) ? (float)$trans->amount : 0,
                'raw_type' => $trans->type,
                'badge'    => $isDebtUs
                    ? '<span class="badge bg-danger bg-opacity-25 text-danger border border-danger rounded-pill">رصيد مدين (لنا)</span>'
                    : ($isDebtThem
                        ? '<span class="badge bg-info bg-opacity-25 text-info border border-info rounded-pill">رصيد دائن (علينا)</span>'
                        : ($trans->type === 'payment'
                            ? '<span class="badge bg-warning bg-opacity-25 text-warning border border-warning rounded-pill">صرف نقدية للورشة</span>'
                            : '<span class="badge bg-success bg-opacity-25 text-success border border-success rounded-pill">قبض نقدية من الورشة</span>')),
                'raw_transaction' => $trans,
            ]);
        }

        foreach($this->invoices as $inv) {
            // Net Amount > 0 means we sold them more than we bought (They owe us) => Debit
            // Net Amount < 0 means we bought from them more than we sold (We owe them) => Credit
            $net = (float)$inv->net_amount;
            
            $allEntries->push([
                'id'     => 'inv_' . $inv->id,
                'source' => 'invoice',
                'date'   => $inv->invoice_date,
                'label'  => 'فاتورة ورشة مزدوجة #' . $inv->invoice_number,
                'credit' => $net < 0 ? abs($net) : 0,
                'debit'  => $net > 0 ? $net : 0,
                'badge'  => '<span class="badge bg-primary bg-opacity-25 text-primary border border-primary rounded-pill">فاتورة ورشة</span>',
            ]);

            if($inv->paid_amount > 0) {
                // Paid amount on invoice means the person who owed the net balance paid part of it.
                // If net > 0 (they owed us), they paid us (Receipt) => Credit.
                // If net < 0 (we owed them), we paid them (Payment) => Debit.
                $isReceipt = $net >= 0;

                $allEntries->push([
                    'id'     => 'inv_pay_' . $inv->id,
                    'source' => 'payment',
                    'date'   => $inv->invoice_date,
                    'label'  => 'سداد دفعة نقدية مع الفاتورة',
                    'credit' => $isReceipt ? (float)$inv->paid_amount : 0,
                    'debit'  => !$isReceipt ? (float)$inv->paid_amount : 0,
                    'badge'  => '<span class="badge bg-info bg-opacity-25 text-info border border-info rounded-pill">سداد نقدي مع الفاتورة</span>',
                ]);
            }
        }

        return $allEntries->sortBy('date');
    }
}
