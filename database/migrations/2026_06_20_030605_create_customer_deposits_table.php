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
        Schema::create('customer_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->string('type')->default('deposit'); // deposit, refund
            $table->string('description')->nullable();
            $table->unsignedBigInteger('order_id')->nullable(); // If tied to a specific order
            $table->timestamps();
        });

        // Add deposit_balance to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('deposit_balance', 12, 2)->default(0)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_deposits');
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('deposit_balance');
        });
    }
};
