<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoxedCage extends Model
{
    protected $fillable = ['product_id', 'loader_id', 'quantity', 'date', 'order_id'];

    protected $casts = [
        'date' => 'date',
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function loader(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'loader_id');
    }

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
