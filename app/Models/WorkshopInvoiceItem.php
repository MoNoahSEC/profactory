<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopInvoiceItem extends Model
{
    protected $fillable = [
        'workshop_invoice_id', 'item_type', 'item_id',
        'transaction_type', 'quantity', 'unit_price', 'total'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function invoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkshopInvoice::class, 'workshop_invoice_id');
    }

    public function rawMaterial(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'item_id');
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }
}
