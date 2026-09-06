<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryRecord extends Model
{
    protected $fillable = [
        'worker_id', 'start_date', 'end_date', 'working_days', 'absent_days',
        'overtime_hours', 'base_salary', 'production_pay', 'overtime_pay', 'deductions',
        'bonuses', 'advances', 'net_salary', 'payment_status', 'payment_date'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'overtime_hours' => 'decimal:2',
        'base_salary' => 'decimal:2',
        'production_pay' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'deductions' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'advances' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function worker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
