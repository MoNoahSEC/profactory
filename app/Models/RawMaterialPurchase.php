<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialPurchase extends Model
{
    protected $fillable = [
        'supplier_id', 'raw_material_id', 'quantity', 'unit_price', 'total_price', 'paid_amount', 'purchase_date', 'notes'
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function rawMaterial(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
