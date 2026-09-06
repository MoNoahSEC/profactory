<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workshop_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            
            // Total values of what we sold to them (raw materials)
            $table->decimal('total_materials_sold', 15, 2)->default(0);
            
            // Total values of what we bought from them (finished products)
            $table->decimal('total_products_bought', 15, 2)->default(0);
            
            // net_amount = total_materials_sold - total_products_bought
            $table->decimal('net_amount', 15, 2)->default(0);
            
            // How much was paid in cash during the invoice creation
            $table->decimal('paid_amount', 15, 2)->default(0);
            
            // How much is remaining (added to their balance)
            $table->decimal('remaining_amount', 15, 2)->default(0);
            
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('workshop_invoices');
    }
};
