<?php require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); 
$w = \App\Models\Worker::find(14); 
$s2 = \App\Models\Setting::get('factory_2_name');
echo 'worker14 bytes: ' . bin2hex($w->factory_location) . PHP_EOL;
echo 'setting2 bytes: ' . bin2hex($s2) . PHP_EOL;

