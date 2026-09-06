<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_id', 'customer_name', 'address', 'order_date', 'expected_date', 'delivery_date', 'status', 'total_amount', 'paid_deposit', 'converted_to_invoice', 'notes', 'loading_status', 'loader_id', 'currency'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'delivery_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_deposit' => 'decimal:2',
        'converted_to_invoice' => 'boolean',
    ];

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function loader(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'loader_id');
    }

    public function shipments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderShipment::class);
    }

    public function getProgressAttribute()
    {
        $totalItems = $this->items->sum('quantity');
        if ($totalItems == 0) return 0;
        
        $loadedItems = $this->items->sum('loaded_quantity');

        $loadedPct = ($loadedItems / $totalItems) * 100;

        return round($loadedPct);
    }

    public function getSmartStatusAttribute()
    {
        if ($this->converted_to_invoice) {
            return 'مفوترة';
        }

        if ($this->status === 'awaiting_approval') {
            return 'بانتظار مراجعة التحميل';
        }

        if ($this->status === 'completed' && $this->loading_status === 'loaded') {
            return 'مكتملة ومسلمة';
        }

        $totalItems = $this->items->sum('quantity');
        $loadedItems = $this->items->sum('loaded_quantity');

        if ($this->loading_status === 'loading') {
            if ($loadedItems > 0 && $loadedItems < $totalItems) {
                return 'جاري التحميل (جزئي)';
            }

            return 'قيد التحميل';
        }

        if ($this->loading_status === 'loaded') {
            return 'مُحمّلة — بانتظار المراجعة';
        }

        if ($this->status === 'in_progress') {
            return 'جارية — لم يبدأ التحميل';
        }

        return 'قيد الانتظار';
    }
}
