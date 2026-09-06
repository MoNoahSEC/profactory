<?php require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); 
$workers = \App\Models\Worker::select('id', 'name', 'factory_location', 'created_at')->orderBy('id', 'desc')->take(10)->get();
foreach($workers as $w) {
    echo $w->id . ' | ' . $w->name . ' | ' . $w->factory_location . ' | ' . $w->created_at . PHP_EOL;
}

