<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$indexes = [
    // Invoices
    "CREATE INDEX IF NOT EXISTS invoices_customer_id_index ON invoices(customer_id)",
    "CREATE INDEX IF NOT EXISTS invoices_date_index ON invoices(date)",
    // Orders
    "CREATE INDEX IF NOT EXISTS orders_customer_id_index ON orders(customer_id)",
    "CREATE INDEX IF NOT EXISTS orders_order_date_index ON orders(order_date)",
    // Advances
    "CREATE INDEX IF NOT EXISTS worker_advances_worker_id_index ON worker_advances(worker_id)",
    "CREATE INDEX IF NOT EXISTS worker_advances_is_deducted_index ON worker_advances(is_deducted)",
    // Attendances
    "CREATE INDEX IF NOT EXISTS attendances_worker_id_index ON attendances(worker_id)",
    "CREATE INDEX IF NOT EXISTS attendances_date_index ON attendances(date)",
    // Worker Productions
    "CREATE INDEX IF NOT EXISTS worker_productions_worker_id_index ON worker_productions(worker_id)",
    "CREATE INDEX IF NOT EXISTS worker_productions_date_index ON worker_productions(date)",
    // Cash Transactions
    "CREATE INDEX IF NOT EXISTS cash_transactions_treasury_id_index ON cash_transactions(treasury_id)",
    "CREATE INDEX IF NOT EXISTS cash_transactions_transaction_date_index ON cash_transactions(transaction_date)",
    "CREATE INDEX IF NOT EXISTS cash_transactions_reference_type_id_index ON cash_transactions(reference_type, reference_id)",
    // Expenses
    "CREATE INDEX IF NOT EXISTS expenses_expense_date_index ON expenses(expense_date)",
    // Salary Records
    "CREATE INDEX IF NOT EXISTS salary_records_worker_id_index ON salary_records(worker_id)",
    "CREATE INDEX IF NOT EXISTS salary_records_week_start_index ON salary_records(week_start_date)",
];

foreach ($indexes as $sql) {
    try {
        DB::statement($sql);
        echo "Successfully ran: $sql\n";
    } catch (\Exception $e) {
        echo "Failed to run $sql: " . $e->getMessage() . "\n";
    }
}

echo "All performance indexes added successfully.\n";
