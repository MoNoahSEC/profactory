<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time', 'overtime_rate'];

    protected $casts = [
        'overtime_rate' => 'decimal:2',
    ];
}
