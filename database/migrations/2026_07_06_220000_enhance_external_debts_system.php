<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Enhance external_debts table ---
        Schema::table('external_debts', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 2)->default(0)->after('amount');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total_amount');
            $table->integer('installments_count')->default(0)->after('paid_amount');
            $table->date('debt_date')->nullable()->after('installments_count');
        });

        // Copy existing amount into total_amount for backward compatibility
        \DB::statement('UPDATE external_debts SET total_amount = amount');
        // Mark already-paid debts
        \DB::statement("UPDATE external_debts SET paid_amount = total_amount WHERE status = 'paid'");

        // --- Create debt_installments table ---
        Schema::create('debt_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_debt_id')->constrained('external_debts')->cascadeOnDelete();
            $table->integer('installment_number');
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['external_debt_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_installments');

        Schema::table('external_debts', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'paid_amount', 'installments_count', 'debt_date']);
        });
    }
};
