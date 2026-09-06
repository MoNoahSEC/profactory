<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemAlert extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'message', 'type', 'is_read'];
}
