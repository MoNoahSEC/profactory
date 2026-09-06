<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->string('worker_type')->default('daily')->after('job_title'); // daily or production
            $table->decimal('shift_wage', 8, 2)->default(0)->after('daily_wage');
            $table->string('factory_location')->default('مصنع 1')->after('shift_wage');
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn(['worker_type', 'shift_wage', 'factory_location']);
        });
    }
};
