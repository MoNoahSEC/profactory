<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->decimal('hourly_wage', 8, 2)->default(0)->after('daily_wage');
            $table->decimal('piece_price', 8, 2)->default(0)->after('hourly_wage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn(['hourly_wage', 'piece_price']);
        });
    }
};
