<?php require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); 
\App\Models\Worker::whereIn('id', [10, 13, 14])->update(['factory_location' => '???? ???????']);
echo 'Updated successfully.';

