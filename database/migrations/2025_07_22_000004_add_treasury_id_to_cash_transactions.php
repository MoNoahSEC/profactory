<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cash_transactions') || Schema::hasColumn('cash_transactions', 'treasury_id')) {
            return;
        }

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->foreignId('treasury_id')->nullable()->after('id')->constrained('treasuries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cash_transactions') || !Schema::hasColumn('cash_transactions', 'treasury_id')) {
            return;
        }

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropForeign(['treasury_id']);
            $table->dropColumn('treasury_id');
        });
    }
};
