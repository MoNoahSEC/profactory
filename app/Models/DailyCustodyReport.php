<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyCustodyReport extends Model
{
    protected $fillable = [
        'report_date', 'opening_custody', 'notes', 'created_by',
    ];

    protected $casts = [
        'report_date'      => 'date',
        'opening_custody'  => 'decimal:2',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(DailyCustodyEntry::class);
    }

    public function totalIn(): float
    {
        return (float) $this->entries()->where('direction', 'in')->sum('amount');
    }

    public function totalOut(): float
    {
        return (float) $this->entries()->where('direction', 'out')->sum('amount');
    }

    /** المبلغ المتوقع تسليمه في نهاية اليوم */
    public function expectedBalance(): float
    {
        return $this->opening_custody + $this->totalIn() - $this->totalOut();
    }

    public static function entryTypeLabels(): array
    {
        return [
            // Incoming (وارد)
            'sales'          => 'تحصيل مبيعات',
            'deposit_return' => 'إرجاع دفعة',
            'other_in'       => 'وارد أخرى',
            // Outgoing (منصرف)
            'expense'        => 'مصروف تشغيلي',
            'purchase'       => 'مشتريات',
            'delivery_cost'  => 'مصاريف توصيل',
            'refund'         => 'مردود عميل',
            'supplier'       => 'دفعة مورد',
            'other_out'      => 'منصرف أخرى',
        ];
    }
}
