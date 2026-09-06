<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('invoices', 'remaining_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('remaining_amount', 12, 2)->default(0)->after('paid_amount');
            });
        }

        DB::statement(
            'UPDATE invoices SET remaining_amount = CASE WHEN (total_amount - paid_amount) < 0 THEN 0 ELSE (total_amount - paid_amount) END'
        );
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'remaining_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('remaining_amount');
            });
        }
    }
};
