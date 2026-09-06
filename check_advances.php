<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Worker;
use App\Models\WorkerAdvance;

echo "=== فحص أرصدة السلف ===\n\n";
$workers = Worker::where('is_active', true)->get();
$hasIssue = false;
foreach ($workers as $w) {
    $real = WorkerAdvance::where('worker_id', $w->id)->where('is_deducted', false)->sum('amount');
    if ($w->pending_advance_balance != $real) {
        $hasIssue = true;
        echo "WARNING  {$w->name}: المحفوظ={$w->pending_advance_balance} | الحقيقي={$real}\n";
    }
}

$total = WorkerAdvance::where('is_deducted', false)->sum('amount');
$totalStored = Worker::where('is_active', true)->sum('pending_advance_balance');

echo "\nاجمالي السلف من جدول السلف: {$total}\n";
echo "اجمالي السلف من جدول العمال: {$totalStored}\n";

if (!$hasIssue) {
    echo "OK لا يوجد أي فرق في الأرصدة!\n";
}

echo "\n=== تفاصيل كل موظف ===\n";
foreach ($workers as $w) {
    $count = WorkerAdvance::where('worker_id', $w->id)->where('is_deducted', false)->count();
    $sum = WorkerAdvance::where('worker_id', $w->id)->where('is_deducted', false)->sum('amount');
    if ($sum > 0) {
        echo "{$w->name}: {$count} سلفة | {$sum} ج.م\n";
    }
}
