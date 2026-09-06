<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

$toDelete = Product::where('code', 'c34')->orWhere('code', 'c22')->get();

foreach ($toDelete as $p) {
    echo "حذف: {$p->name} (كود: {$p->code})\n";
    if ($p->image_path && file_exists(public_path($p->image_path))) {
        @unlink(public_path($p->image_path));
    }
    $p->delete();
}

echo "تم الحذف بنجاح!\n";
