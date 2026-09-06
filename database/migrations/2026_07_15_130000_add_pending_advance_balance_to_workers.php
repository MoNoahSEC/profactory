<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Worker;
use App\Models\WorkerAdvance;
use App\Models\SalaryRecord;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->decimal('pending_advance_balance', 10, 2)->default(0)->after('daily_wage_type')
                ->comment('إجمالي رصيد السلف المتبقية غير المخصومة');
        });

        // احسب الرصيد الحالي لكل موظف من السلف القائمة
        Worker::each(function (Worker $worker) {
            $pending = WorkerAdvance::where('worker_id', $worker->id)
                ->where('is_deducted', false)
                ->sum('amount');
            $worker->update(['pending_advance_balance' => $pending]);
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn('pending_advance_balance');
        });
    }
};
