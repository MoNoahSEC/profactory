<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerPenalty extends Model
{
    protected $fillable = [
        'worker_id',
        'amount',
        'reason_type',
        'notes',
        'date',
        'is_deducted',
    ];

    protected $casts = [
        'date' => 'date',
        'is_deducted' => 'boolean',
    ];

    public function worker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
