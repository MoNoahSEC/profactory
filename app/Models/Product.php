<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'code', 'name', 'description', 
        'dimensions', 'cages_per_carton', 'selling_price', 'labor_cost', 'scissors_cost', 'overhead_cost', 
        'plastic_weight', 'plastic_price_per_kg', 'paint_cost',
        'is_active', 'image_path', 'shift_target_quantity',
        'piece_wage', 'piece_wage_scissors'
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'scissors_cost' => 'decimal:2',
        'overhead_cost' => 'decimal:2',
        'plastic_weight' => 'decimal:3',
        'plastic_price_per_kg' => 'decimal:2',
        'paint_cost' => 'decimal:2',
        'is_active' => 'boolean',
        'piece_wage' => 'decimal:4',
        'piece_wage_scissors' => 'decimal:4',
        'cages_per_carton' => 'integer',
    ];

    public function getTotalCostAttribute()
    {
        $plasticCost = ($this->plastic_weight ?? 0) * ($this->plastic_price_per_kg ?? 0);
        return ($this->labor_cost ?? 0) + 
               ($this->scissors_cost ?? 0) + 
               ($this->overhead_cost ?? 0) + 
               ($this->paint_cost ?? 0) + 
               $plasticCost;
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function materials(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(RawMaterial::class, 'product_materials')
                    ->withPivot('quantity_needed')
                    ->withTimestamps();
    }

    public function productionOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function inventory(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function invoiceItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
