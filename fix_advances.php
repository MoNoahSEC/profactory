<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Worker;
use App\Models\WorkerAdvance;

echo "=== إصلاح أرصدة السلف ===\n\n";
$workers = Worker::where('is_active', true)->get();
$fixed = 0;

foreach ($workers as $w) {
    $real = WorkerAdvance::where('worker_id', $w->id)
        ->where('is_deducted', false)
        ->sum('amount');
    
    if ($w->pending_advance_balance != $real) {
        $w->update(['pending_advance_balance' => $real]);
        echo "تم تصحيح: {$w->name} -> {$real} ج.م\n";
        $fixed++;
    }
}

echo "\n=== تم تصحيح {$fixed} موظف ===\n";
$total = WorkerAdvance::where('is_deducted', false)->sum('amount');
echo "إجمالي السلف الصحيح الآن: {$total} ج.م\n";
