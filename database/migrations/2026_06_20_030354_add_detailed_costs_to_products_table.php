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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('plastic_weight', 8, 3)->default(0)->after('overhead_cost');
            $table->decimal('plastic_price_per_kg', 10, 2)->default(0)->after('plastic_weight');
            $table->decimal('paint_cost', 10, 2)->default(0)->after('plastic_price_per_kg');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['plastic_weight', 'plastic_price_per_kg', 'paint_cost']);
        });
    }
};
