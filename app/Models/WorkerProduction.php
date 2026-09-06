<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerProduction extends Model
{
    protected $fillable = [
        'worker_id', 'product_id', 'production_role',
        'machinist_worker_id', 'scissors_worker_id', 'paired_production_id',
        'date', 'quantity', 'labor_cost_per_piece', 'total_pay',
        'inventory_added', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'labor_cost_per_piece' => 'decimal:2',
        'total_pay' => 'decimal:2',
        'inventory_added' => 'boolean',
    ];

    public function worker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function machinistWorker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Worker::class, 'machinist_worker_id');
    }

    public function scissorsWorker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Worker::class, 'scissors_worker_id');
    }

    public function pairedProduction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'paired_production_id');
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->production_role) {
            'scissors' => 'مقص',
            'machinist' => 'مكنجي',
            default => $this->production_role,
        };
    }
}
