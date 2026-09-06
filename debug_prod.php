<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

use App\Models\WorkerProduction;
use App\Models\Worker;

$w = Worker::where('worker_type', 'production')->first();
if ($w) {
    echo "Worker: " . $w->name . " type: " . $w->worker_type . " role: " . $w->production_role . "\n";
    $prods = WorkerProduction::where('worker_id', $w->id)->latest('date')->take(5)->get();
    foreach ($prods as $p) {
        echo "ID: " . $p->id . " Date: " . $p->date . " Qty: " . $p->quantity . " inv_added: " . ($p->inventory_added ? 'yes' : 'no') . "\n";
    }
    
    // Try to directly delete one of these and see if it works
    echo "\nTotal productions for this worker: " . WorkerProduction::where('worker_id', $w->id)->count() . "\n";
} else {
    echo "No production workers found\n";
}
