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
        Schema::table('salary_records', function (Blueprint $table) {
            if (!Schema::hasColumn('salary_records', 'bonus')) {
                $table->decimal('bonus', 10, 2)->default(0)->after('net_salary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            if (Schema::hasColumn('salary_records', 'bonus')) {
                $table->dropColumn('bonus');
            }
        });
    }
};
