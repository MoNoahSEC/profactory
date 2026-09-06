<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $start = microtime(true);
    $users = \App\Models\User::all();
    $end = microtime(true);
    echo "Query OK in " . ($end - $start) . " seconds\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
