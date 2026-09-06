<?php require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); 
$w = \App\Models\Worker::create([
    'name' => 'Test Worker Factory',
    'hire_date' => '2026-07-30',
    'job_title' => 'Test',
    'worker_type' => 'daily',
    'shift_type' => 'morning',
    'factory_location' => '???? ???????',
    'code' => 'W-999',
]);
echo 'Created: ' . $w->factory_location . PHP_EOL;

