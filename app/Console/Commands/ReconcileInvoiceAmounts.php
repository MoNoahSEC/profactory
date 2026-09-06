<?php

namespace App\Console\Commands;

use App\Services\InvoiceAccountingService;
use Illuminate\Console\Command;

class ReconcileInvoiceAmounts extends Command
{
    protected $signature = 'invoices:reconcile-amounts';

    protected $description = 'إعادة حساب remaining_amount وحالة كل الفواتير من paid_amount';

    public function handle(InvoiceAccountingService $accounting): int
    {
        $updated = $accounting->recalculateAll();
        $this->info("تمت مراجعة الفواتير. عدد الفواتير التي تغيّر متبقيها: {$updated}");

        return self::SUCCESS;
    }
}
