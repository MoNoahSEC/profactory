<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$start = microtime(true);
\App\Models\Worker::create([
    'name' => 'test_worker_' . time(),
    'code' => 'W-' . time(),
    'worker_type' => 'daily',
    'shift_type' => 'morning',
    'factory_location' => 'cairo',
    'is_active' => true
]);
$end = microtime(true);
echo 'DONE in ' . ($end - $start) . ' seconds';
