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
        Schema::create('order_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('loader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('driver_name')->nullable();
            $table->string('truck_details')->nullable(); // e.g. plate number
            $table->text('notes')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();
        });
        
        // Also need a pivot table or JSON for what was shipped in this specific shipment?
        // Wait, if an order has multiple shipments, we need to know what items were in this shipment!
        // Let's create an `order_shipment_items` table.
        Schema::create('order_shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_shipment_id')->constrained('order_shipments')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipment_items');
        Schema::dropIfExists('order_shipments');
    }
};
