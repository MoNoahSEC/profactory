<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $w = \App\Models\Worker::create([
        'name' => 'test_worker_1', 
        'worker_type' => 'daily', 
        'shift_type' => 'morning', 
        'factory_location' => 'cairo', 
        'is_active' => true, 
        'daily_wage' => 0, 
        'hourly_wage' => 0, 
        'shift_wage' => 0, 
        'wage_system' => 'shift', 
        'code' => 'W-999'
    ]);
    echo "SUCCESS: " . $w->id;
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
