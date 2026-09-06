<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Treasury;
use App\Models\CashTransaction;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cash_transactions') || !Schema::hasTable('treasuries')) {
            return;
        }

        // 1. Check if we already have transactions without treasury_id
        $unlinkedTransactionsCount = DB::table('cash_transactions')->whereNull('treasury_id')->count();

        // 2. Create the default main treasury if it doesn't exist
        $defaultTreasury = Treasury::firstOrCreate(
            ['is_default' => true],
            [
                'name' => 'الخزينة الرئيسية (نقدية)',
                'type' => 'cash',
                'is_active' => true,
                'initial_balance' => 0,
                'current_balance' => (float) Setting::get('cash_balance', 0)
            ]
        );

        // 3. Link all unlinked transactions to the default treasury
        if ($unlinkedTransactionsCount > 0) {
            DB::table('cash_transactions')
                ->whereNull('treasury_id')
                ->update(['treasury_id' => $defaultTreasury->id]);
        }
    }

    public function down(): void
    {
        // Reversing this is tricky, we just leave it.
    }
};
