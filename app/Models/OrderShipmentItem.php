<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderShipmentItem extends Model
{
    protected $fillable = ['order_shipment_id', 'order_item_id', 'quantity'];

    public function shipment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(OrderShipment::class, 'order_shipment_id');
    }

    public function orderItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            OrderItem::class,
            'id',
            'id',
            'order_item_id',
            'product_id'
        );
    }
}
