<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    protected $fillable = [
        'treasury_id', 'type', 'amount', 'balance_after', 'description',
        'transaction_date', 'reference_type', 'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function reference(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public static function typeLabels(): array
    {
        return [
            'deposit' => 'إيداع نقدي',
            'expense' => 'مصروف',
            'payment' => 'تحصيل فاتورة',
            'salary' => 'صرف راتب',
            'order_deposit' => 'عربون طلبية',
            'worker_advance' => 'سلفة موظف',
            'supplier_payment' => 'دفعة مورد',
            'external_debt_in' => 'سداد دين لنا',
            'external_debt_out' => 'سداد دين علينا',
            'installment_in' => 'قسط مستلم (دين لنا)',
            'installment_out' => 'قسط مدفوع (دين علينا)',
            'adjustment' => 'تعديل رصيد',
        ];
    }

    public static function isInType(string $type): bool
    {
        return in_array($type, ['deposit', 'payment', 'order_deposit', 'external_debt_in']);
    }

    public static function record(string $type, float $amount, string $description, ?Model $reference = null, $date = null)
    {
        if ($amount <= 0) return null;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($type, $amount, $description, $reference, $date) {
            $lastTx = self::orderBy('id', 'desc')->lockForUpdate()->first();
            $currentBalance = $lastTx ? $lastTx->balance_after : 0;

            $isIn = self::isInType($type);
            $newBalance = $isIn ? $currentBalance + $amount : $currentBalance - $amount;

            $tx = new self([
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $description,
                'transaction_date' => $date ?? \Carbon\Carbon::today(),
            ]);

            if ($reference) {
                $tx->reference()->associate($reference);
            }

            $tx->save();

            return $tx;
        });
    }

    public function currentBalance()
    {
        $lastTx = self::orderBy('id', 'desc')->first();
        return $lastTx ? $lastTx->balance_after : 0;
    }

    public function treasury(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }
}
