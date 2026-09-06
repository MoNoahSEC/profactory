<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerBonus extends Model
{
    protected $fillable = ['worker_id', 'amount', 'date', 'is_paid', 'notes'];

    protected $casts = [
        'is_paid' => 'boolean',
        'date' => 'date',
    ];

    public function worker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
