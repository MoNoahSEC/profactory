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
        Schema::create('supplier_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->string('type')->default('deposit'); // deposit, debt_adjustment
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Add deposit_balance to suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('deposit_balance', 12, 2)->default(0)->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_deposits');
        
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('deposit_balance');
        });
    }
};
