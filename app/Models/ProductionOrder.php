<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    protected $fillable = [
        'order_number', 'product_id', 'quantity_ordered',
        'quantity_produced', 'production_date', 'status',
        'notes', 'created_by'
    ];

    protected $casts = [
        'production_date' => 'date',
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cost(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProductionCost::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
