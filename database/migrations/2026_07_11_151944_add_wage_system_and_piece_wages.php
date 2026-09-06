<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة نظام الأجر للعامل: 'shift' أو 'piece'
        Schema::table('workers', function (Blueprint $table) {
            $table->string('wage_system')->default('shift')->after('shift_wage');
        });

        // إضافة سعر القطعة لكل قفص (للمكنجي وللمقص)
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('piece_wage', 10, 4)->default(0)->after('shift_target_quantity');
            $table->decimal('piece_wage_scissors', 10, 4)->default(0)->after('piece_wage');
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn('wage_system');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['piece_wage', 'piece_wage_scissors']);
        });
    }
};
