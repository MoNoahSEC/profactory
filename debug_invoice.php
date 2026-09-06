<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get the last invoice and its items
$invoice = \App\Models\Invoice::with('items.product')->latest()->first();
if ($invoice) {
    echo "Invoice: " . $invoice->invoice_number . "\n";
    echo "Total: " . $invoice->total_amount . "\n";
    echo "Items count: " . $invoice->items->count() . "\n";
    foreach ($invoice->items as $item) {
        echo "  - Product: " . ($item->product->name ?? 'NULL PRODUCT') . " | product_id: " . $item->product_id . " | qty: " . $item->quantity . " | price: " . $item->unit_price . " | total: " . $item->total . "\n";
    }
} else {
    echo "No invoices found.\n";
}
