<?php require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); 
$schema = \Illuminate\Support\Facades\DB::select('SELECT type, name, tbl_name FROM sqlite_master WHERE type=\'index\'');
foreach($schema as $idx) {
    if(!str_starts_with($idx->name, 'sqlite_')) {
        echo $idx->tbl_name . ' -> ' . $idx->name . PHP_EOL;
    }
}

