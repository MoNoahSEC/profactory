<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workshop_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_invoice_id')->constrained()->cascadeOnDelete();
            
            // Can be 'raw_material' (sold to them) or 'product' (bought from them)
            $table->string('item_type');
            
            // Reference to raw_materials.id or products.id
            $table->unsignedBigInteger('item_id');
            
            // 'sell' (materials to workshop) or 'buy' (products from workshop)
            $table->string('transaction_type');
            
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('workshop_invoice_items');
    }
};
