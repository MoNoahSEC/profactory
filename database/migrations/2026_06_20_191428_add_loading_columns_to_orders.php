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
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('loading_status', ['pending', 'loading', 'loaded'])->default('pending')->after('status');
            $table->foreignId('loader_id')->nullable()->constrained('users')->nullOnDelete()->after('loading_status');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('loaded_quantity')->default(0)->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['loader_id']);
            $table->dropColumn(['loading_status', 'loader_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('loaded_quantity');
        });
    }
};
