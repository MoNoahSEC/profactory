<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treasury extends Model
{
    protected $fillable = [
        'name',
        'type',
        'initial_balance',
        'current_balance',
        'is_active',
        'is_default',
        'notes'
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public static function defaultTreasury(): ?self
    {
        return self::where('is_default', true)->first() ?? self::first();
    }
}
