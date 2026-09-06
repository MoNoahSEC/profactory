<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderShipment extends Model
{
    protected $fillable = [
        'order_id', 'loader_id', 'driver_id', 'driver_name', 'truck_details', 'notes', 'shipped_at', 'status', 'show_invoice', 'is_cash_collected'
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'show_invoice' => 'boolean',
        'is_cash_collected' => 'boolean',
    ];

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function loader(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'loader_id');
    }
    
    public function driver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderShipmentItem::class);
    }
    
    public function locations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DriverLocation::class);
    }
}
