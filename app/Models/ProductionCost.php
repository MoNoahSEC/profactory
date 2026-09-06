<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionCost extends Model
{
    protected $fillable = [
        'production_order_id', 'material_cost', 'labor_cost',
        'overhead_cost', 'total_cost', 'cost_per_unit',
        'profit_per_unit', 'profit_margin'
    ];

    protected $casts = [
        'material_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'overhead_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'profit_per_unit' => 'decimal:2',
        'profit_margin' => 'decimal:2',
    ];

    public function productionOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }
}
