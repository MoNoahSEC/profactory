<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    protected $fillable = [
        'code', 'name', 'national_id', 'phone', 'address', 'hire_date',
        'job_title', 'worker_type', 'production_role', 'shift_type',
        'daily_wage', 'hourly_wage', 'piece_price', 'shift_wage',
        'wage_system', 'factory_location', 'is_active', 'notes', 'daily_wage_type',
        'pending_advance_balance'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'daily_wage' => 'decimal:2',
        'hourly_wage' => 'decimal:2',
        'piece_price' => 'decimal:2',
        'shift_wage' => 'decimal:2',
        'pending_advance_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getApplicableWageAttribute()
    {
        if ($this->worker_type === 'production') {
            // نظام القطعة: السعر من إعدادات المنتج
            if ($this->wage_system === 'piece') {
                return 0; // السعر يُؤخذ من المنتج مباشرة
            }
            // نظام الورديات
            return $this->shift_wage;
        }
        return $this->hourly_wage > 0 ? $this->hourly_wage : $this->daily_wage;
    }

    public function isPieceSystem(): bool
    {
        return $this->worker_type === 'production' && $this->wage_system === 'piece';
    }

    public function isShiftSystem(): bool
    {
        return $this->worker_type === 'production' && $this->wage_system !== 'piece';
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function salaryRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalaryRecord::class);
    }

    public function productions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkerProduction::class);
    }

    public function advances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkerAdvance::class);
    }

    /** رصيد السلف غير المخصومة */
    public function getPendingAdvancesAttribute()
    {
        return $this->advances()->where('is_deducted', false)->get();
    }

    public function scopeMachinists($query)
    {
        return $query->where('worker_type', 'production')->where('production_role', 'machinist');
    }

    public function scopeScissors($query)
    {
        return $query->where('worker_type', 'production')->where('production_role', 'scissors');
    }

    public function getRoleLabelAttribute(): string
    {
        if ($this->worker_type !== 'production') {
            return 'يومية';
        }

        return match ($this->production_role) {
            'scissors' => 'مقص',
            'machinist' => 'مكنجي',
            default => 'إنتاج',
        };
    }
}

