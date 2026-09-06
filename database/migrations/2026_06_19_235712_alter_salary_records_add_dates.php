<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('worker_id');
            $table->date('end_date')->nullable()->after('start_date');
            $table->dropColumn('month');
            $table->decimal('advances', 10, 2)->default(0)->after('bonuses');
            $table->decimal('net_salary', 10, 2)->default(0)->after('advances');
        });
    }

    public function down(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'advances', 'net_salary']);
            $table->string('month')->after('worker_id');
        });
    }
};
