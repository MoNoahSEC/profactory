<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCustodyEntry extends Model
{
    protected $fillable = [
        'daily_custody_report_id', 'direction', 'type', 'amount', 'description', 'entry_time',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(DailyCustodyReport::class, 'daily_custody_report_id');
    }

    public function isIn(): bool
    {
        return $this->direction === 'in';
    }
}
