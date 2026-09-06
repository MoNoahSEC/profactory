<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workshop_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            
            // type: 'payment' (we paid them), 'receipt' (they paid us), 'debt_adjustment'
            $table->string('type');
            
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('description')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('workshop_transactions');
    }
};
