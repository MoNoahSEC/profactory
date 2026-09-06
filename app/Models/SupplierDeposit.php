<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierDeposit extends Model
{
    protected $fillable = [
        'supplier_id', 'amount', 'date', 'type', 'description'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
