<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Inventory index already created before failure
        // Schema::table('inventory', function (Blueprint $table) {
        //     $table->unique('product_id', 'inventory_product_id_unique');
        // });

        Schema::table('attendances', function (Blueprint $table) {
            // إضافة قيد فريد لمنع تسجيل حضور العامل مرتين في نفس اليوم
            $table->unique(['worker_id', 'date'], 'attendances_worker_date_unique');
        });
    }

    public function down()
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropUnique('inventory_product_id_unique');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_worker_date_unique');
        });
    }
};
