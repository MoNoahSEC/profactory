<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('scissors_cost', 10, 2)->default(0)->after('labor_cost');
        });

        Schema::table('workers', function (Blueprint $table) {
            $table->string('production_role')->nullable()->after('worker_type');
        });

        Schema::table('worker_productions', function (Blueprint $table) {
            $table->string('production_role')->default('machinist')->after('product_id');
            $table->foreignId('machinist_worker_id')->nullable()->after('worker_id')->constrained('workers')->nullOnDelete();
            $table->foreignId('scissors_worker_id')->nullable()->after('machinist_worker_id')->constrained('workers')->nullOnDelete();
            $table->foreignId('paired_production_id')->nullable()->after('scissors_worker_id')->constrained('worker_productions')->nullOnDelete();
            $table->boolean('inventory_added')->default(false)->after('total_pay');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->date('expected_date')->nullable()->after('order_date');
            $table->date('delivery_date')->nullable()->after('expected_date');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['expected_date', 'delivery_date']);
        });

        Schema::table('worker_productions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paired_production_id');
            $table->dropConstrainedForeignId('scissors_worker_id');
            $table->dropConstrainedForeignId('machinist_worker_id');
            $table->dropColumn(['production_role', 'inventory_added']);
        });

        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn('production_role');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('scissors_cost');
        });
    }
};
