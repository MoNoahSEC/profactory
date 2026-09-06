<?php require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); 
$w = \App\Models\Worker::find(14); 
$s1 = \App\Models\Setting::get('factory_1_name');
$s2 = \App\Models\Setting::get('factory_2_name');
echo 'worker14 factory: [' . $w->factory_location . ']' . PHP_EOL;
echo 'setting1: [' . $s1 . ']' . PHP_EOL;
echo 'setting2: [' . $s2 . ']' . PHP_EOL;

