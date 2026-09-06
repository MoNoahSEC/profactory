<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    protected $fillable = [
        'material_category_id', 'name', 'unit', 'unit_cost', 'current_stock',
        'minimum_stock', 'supplier_name', 'notes'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'current_stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_materials')
                    ->withPivot('quantity_needed')
                    ->withTimestamps();
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }
}
