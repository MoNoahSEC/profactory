<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopTransaction extends Model
{
    protected $fillable = [
        'workshop_id', 'type', 'amount', 'date', 'description', 'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function workshop(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
