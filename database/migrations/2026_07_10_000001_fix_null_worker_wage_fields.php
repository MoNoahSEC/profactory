<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('workers')->whereNull('piece_price')->update(['piece_price' => 0]);
        DB::table('workers')->whereNull('shift_wage')->update(['shift_wage' => 0]);
        DB::table('workers')->whereNull('hourly_wage')->update(['hourly_wage' => 0]);
        DB::table('workers')->whereNull('daily_wage')->update(['daily_wage' => 0]);
    }

    public function down(): void
    {
        // لا يمكن استرجاع القيم null الأصلية
    }
};
