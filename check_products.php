<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

$products = Product::pluck('name', 'code')->toArray();
foreach($products as $code => $name) {
    echo "[$code] => $name\n";
}
