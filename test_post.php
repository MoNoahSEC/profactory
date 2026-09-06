<?php require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); 
$request = \Illuminate\Http\Request::create('/workers', 'POST', [
    'name' => 'Test Worker POST',
    'hire_date' => '2026-07-30',
    'job_title' => 'Test',
    'worker_type' => 'daily',
    'shift_type' => 'morning',
    'factory_location' => '???? ???????',
    'daily_wage' => 100
]);
// bypass auth
Auth::login(\App\Models\User::first());
$controller = new \App\Http\Controllers\WorkerController();
$response = $controller->store($request);
echo 'Response Status: ' . $response->getStatusCode() . PHP_EOL;
$w = \App\Models\Worker::latest()->first();
echo 'Saved factory: ' . $w->factory_location . PHP_EOL;

