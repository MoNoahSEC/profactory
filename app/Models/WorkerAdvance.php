<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerAdvance extends Model
{
    protected $fillable = ['worker_id', 'amount', 'date', 'is_deducted', 'notes'];

    protected $casts = [
        'is_deducted' => 'boolean',
        'date' => 'date',
    ];

    public function worker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
