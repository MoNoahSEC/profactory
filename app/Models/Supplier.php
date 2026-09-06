<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name', 'phone', 'company_name', 'address', 'total_debt', 'notes', 'deposit_balance'
    ];

    public function purchases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RawMaterialPurchase::class);
    }

    public function deposits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupplierDeposit::class);
    }

    public function getStatement()
    {
        $allEntries = collect();

        foreach($this->deposits as $dep) {
            $isDebt = $dep->type === 'debt_adjustment';
            $allEntries->push([
                'id'     => $dep->id,
                'source' => 'deposit',
                'date'   => $dep->date,
                'label'  => $dep->description ?? 'دفعة نقدية / تسوية',
                'credit' => $isDebt ? 0 : (float)$dep->amount,
                'debit'  => $isDebt ? (float)$dep->amount : 0,
                'raw_type' => $dep->type,
                'badge'  => $isDebt
                    ? '<span class="badge bg-danger bg-opacity-25 text-danger border border-danger rounded-pill">دين قديم للمورد</span>'
                    : '<span class="badge bg-success bg-opacity-25 text-success border border-success rounded-pill">دفعة من الخزينة</span>',
            ]);
        }

        foreach($this->purchases as $pur) {
            $allEntries->push([
                'id'     => $pur->id,
                'source' => 'purchase',
                'date'   => $pur->purchase_date,
                'label'  => 'فاتورة مشتريات خامات',
                'credit' => 0,
                'debit'  => (float)$pur->total_price,
                'badge'  => '<span class="badge bg-warning bg-opacity-25 text-warning border border-warning rounded-pill">مشتريات</span>',
            ]);
            if($pur->paid_amount > 0) {
                $allEntries->push([
                    'id'     => $pur->id . '_pay',
                    'source' => 'payment',
                    'date'   => $pur->purchase_date,
                    'label'  => 'سداد جزء من الفاتورة',
                    'credit' => (float)$pur->paid_amount,
                    'debit'  => 0,
                    'badge'  => '<span class="badge bg-info bg-opacity-25 text-info border border-info rounded-pill">سداد</span>',
                ]);
            }
        }

        return $allEntries->sortBy('date');
    }
}
