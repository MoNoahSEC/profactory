<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopInvoice extends Model
{
    protected $fillable = [
        'workshop_id', 'invoice_number', 'invoice_date',
        'total_materials_sold', 'total_products_bought',
        'net_amount', 'paid_amount', 'remaining_amount',
        'notes', 'created_by'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'total_materials_sold' => 'decimal:2',
        'total_products_bought' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function workshop(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkshopInvoiceItem::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
