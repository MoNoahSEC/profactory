<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_custody_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique(); // one report per day
            $table->decimal('opening_custody', 12, 2)->default(0); // العهدة المستلمة في أول اليوم
            $table->string('notes')->nullable();
            $table->string('created_by')->nullable(); // اسم المسؤول عن العهدة
            $table->timestamps();
        });

        Schema::create('daily_custody_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_custody_report_id')->constrained()->cascadeOnDelete();
            $table->enum('direction', ['in', 'out']); // وارد أو منصرف
            $table->string('type'); // نوع الحركة: مبيعات، مصروف، إرجاع، خلافه
            $table->decimal('amount', 12, 2);
            $table->string('description'); // البيان التفصيلي
            $table->time('entry_time')->nullable(); // وقت الحركة
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_custody_entries');
        Schema::dropIfExists('daily_custody_reports');
    }
};
