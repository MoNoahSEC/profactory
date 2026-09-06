<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CashTransaction;
use App\Services\CashLedgerService;

echo "Starting cleanup...\n";

// 1. Delete all reversal transactions
$reversals = CashTransaction::where('type', 'like', '%reversal%')->get();
foreach ($reversals as $rev) {
    echo "Deleting reversal: {$rev->id} - {$rev->type} - {$rev->amount}\n";
    $rev->delete();
}

// 2. Delete any CashTransaction where the reference model no longer exists
$txs = CashTransaction::whereNotNull('reference_type')->get();
foreach ($txs as $tx) {
    $class = $tx->reference_type;
    if (class_exists($class)) {
        $model = $class::find($tx->reference_id);
        if (!$model) {
            echo "Deleting orphaned transaction: {$tx->id} - {$tx->type} - ref: {$tx->reference_type}:{$tx->reference_id}\n";
            $tx->delete();
        }
    }
}

// 3. Recalculate ledger
app(CashLedgerService::class)->recalculateLedger();
echo "Ledger recalculated successfully.\n";
