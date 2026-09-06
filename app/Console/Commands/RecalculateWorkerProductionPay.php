<?php

namespace App\Console\Commands;

use App\Services\WorkerWageCalculationService;
use Illuminate\Console\Command;

class RecalculateWorkerProductionPay extends Command
{
    protected $signature = 'workers:recalculate-pay {--from=} {--to=}';

    protected $description = 'إعادة حساب أجور سجلات الإنتاج من إعدادات العمال الحالية';

    public function handle(WorkerWageCalculationService $wageService): int
    {
        $from = $this->option('from') ?: now()->subMonths(3)->toDateString();
        $to = $this->option('to') ?: now()->toDateString();

        $updated = $wageService->recalculateForPeriod($from, $to);

        $this->info("تم تحديث {$updated} سجل إنتاج للفترة من {$from} إلى {$to}.");

        return self::SUCCESS;
    }
}
