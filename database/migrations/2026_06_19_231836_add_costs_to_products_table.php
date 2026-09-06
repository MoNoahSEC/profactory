<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('labor_cost', 10, 2)->default(0)->after('selling_price');
            $table->decimal('overhead_cost', 10, 2)->default(0)->after('labor_cost');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['labor_cost', 'overhead_cost']);
        });
    }
};
