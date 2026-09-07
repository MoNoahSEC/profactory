<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;

Route::get('/debug-db', function () {
    return 'Customers: ' . \App\Models\Customer::count() . ' - DB: ' . config('database.connections.sqlite.database');
});

// --- ROOT ROUTE REDIRECT ---
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->hasAnyRole(['Admin', 'Cashier', 'Storekeeper'])) return redirect()->route('dashboard');
        if ($user->hasAnyRole(['Supervisor', 'HR'])) return redirect()->route('salaries.index');
        if ($user->hasRole('Loader')) return redirect()->route('loading.index');
        if ($user->hasRole('Driver')) return redirect()->route('driver.dashboard');
    }
    return redirect()->route('dashboard');
});


// --- PUBLIC MENU ---
Route::get('/menu', [\App\Http\Controllers\PublicMenuController::class, 'index'])->name('menu.index');
Route::get('/menu/pdf', [\App\Http\Controllers\PublicMenuController::class, 'downloadPdf'])->name('menu.pdf');

Route::post('/alerts/{alert}/read', function (\App\Models\SystemAlert $alert) {
    $alert->update(['is_read' => true]);
    return back();
})->name('alerts.read')->middleware('auth');

Route::get('/dev/migrate', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        return $e->getMessage();
    }
});

Route::middleware(['auth'])->group(function () {

    // --- ADMIN SYSTEM (FULL ACCESS FOR ADMIN & STAFF) ---
    Route::middleware(['role:Admin|Cashier|Storekeeper'])->group(function () {
        // Admin Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // System Settings & Users
        Route::resource('users', UserController::class);
        
        // Worker Product Prices
        Route::get('worker-prices', [\App\Http\Controllers\WorkerProductPriceController::class, 'index'])->name('worker-prices.index');
        Route::post('worker-prices', [\App\Http\Controllers\WorkerProductPriceController::class, 'store'])->name('worker-prices.store');
        
        // Settings
        Route::get('settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/wipe', [\App\Http\Controllers\SettingController::class, 'wipeDatabase'])->name('settings.wipe');
        Route::get('settings/backup', [\App\Http\Controllers\SettingController::class, 'backupDatabase'])->name('settings.backup');
        Route::post('settings/zero-treasury', [\App\Http\Controllers\SettingController::class, 'zeroTreasury'])->name('settings.zeroTreasury');
        Route::post('settings/zero-advances', [\App\Http\Controllers\SettingController::class, 'zeroAdvances'])->name('settings.zeroAdvances');
        
        // Public Menu Publish
        Route::post('/menu/publish', [\App\Http\Controllers\PublicMenuController::class, 'publishToGitHub'])->name('menu.publish');

        // Products & Categories
        Route::resource('categories', \App\Http\Controllers\CategoryController::class);
        Route::resource('material-categories', \App\Http\Controllers\MaterialCategoryController::class)->except(['index', 'create', 'edit', 'show']);
        Route::get('products/inventory-print', [\App\Http\Controllers\ProductController::class, 'printInventory'])->name('products.inventory-print');
        Route::resource('products', \App\Http\Controllers\ProductController::class);
        Route::get('products/{product}/cost', [\App\Http\Controllers\ProductController::class, 'showCost'])->name('products.cost');
        Route::post('products/{product}/materials', [\App\Http\Controllers\ProductController::class, 'saveMaterials'])->name('products.materials.save');

        // Inventory
        Route::get('inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
        Route::post('inventory/adjust', [\App\Http\Controllers\InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::post('inventory/minimum', [\App\Http\Controllers\InventoryController::class, 'updateMinimumStock'])->name('inventory.minimum');
        Route::post('inventory/quick-update', [\App\Http\Controllers\InventoryController::class, 'quickUpdate'])->name('inventory.quick-update');

        // Raw Materials, Suppliers & Purchases
        Route::resource('raw-materials', \App\Http\Controllers\RawMaterialController::class);
        Route::post('raw-materials/{raw_material}/restock', [\App\Http\Controllers\RawMaterialController::class, 'restock'])->name('raw-materials.restock');
        Route::resource('suppliers', \App\Http\Controllers\SupplierController::class)->except(['create', 'edit']);
        Route::post('suppliers/{supplier}/pay', [\App\Http\Controllers\SupplierController::class, 'storePayment'])->name('suppliers.pay');
        Route::post('suppliers/{supplier}/deposit', [\App\Http\Controllers\SupplierController::class, 'storeDeposit'])->name('suppliers.deposit');
        Route::post('suppliers/{supplier}/adjust-balance', [\App\Http\Controllers\SupplierController::class, 'adjustBalance'])->name('suppliers.adjustBalance');
        Route::put('suppliers/adjustments/{deposit}', [\App\Http\Controllers\SupplierController::class, 'updateAdjustment'])->name('suppliers.updateAdjustment');
        Route::delete('suppliers/adjustments/{deposit}', [\App\Http\Controllers\SupplierController::class, 'destroyAdjustment'])->name('suppliers.destroyAdjustment');
        Route::get('suppliers/{supplier}/print', [\App\Http\Controllers\SupplierController::class, 'print'])->name('suppliers.print');
        Route::resource('purchases', \App\Http\Controllers\RawMaterialPurchaseController::class)->except(['show']);
        Route::get('purchases/{purchase}/print', [\App\Http\Controllers\RawMaterialPurchaseController::class, 'print'])->name('purchases.print');

        // Orders (Production)
        Route::resource('orders', \App\Http\Controllers\OrderController::class);
        Route::post('orders/{order}/status', [\App\Http\Controllers\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('orders/{order}/invoice', [\App\Http\Controllers\OrderController::class, 'convertToInvoice'])->name('orders.invoice');
        Route::get('orders/{order}/print', [\App\Http\Controllers\OrderController::class, 'print'])->name('orders.print');
        Route::post('orders/{order}/deposit', [\App\Http\Controllers\OrderController::class, 'addDeposit'])->name('orders.addDeposit');
        Route::post('orders/{order}/send-to-loading', [\App\Http\Controllers\OrderController::class, 'sendToLoading'])->name('orders.send-to-loading');

        // Customers & Invoices & Payments
        Route::resource('customers', \App\Http\Controllers\CustomerController::class);
        Route::post('/customers/{customer}/deposit', [\App\Http\Controllers\CustomerController::class, 'storeDeposit'])->name('customers.deposit');
        Route::post('/customers/{customer}/adjust-balance', [\App\Http\Controllers\CustomerController::class, 'adjustBalance'])->name('customers.adjustBalance');
        Route::put('/customers/adjustments/{deposit}', [\App\Http\Controllers\CustomerController::class, 'updateAdjustment'])->name('customers.updateAdjustment');
        Route::delete('/customers/adjustments/{deposit}', [\App\Http\Controllers\CustomerController::class, 'destroyAdjustment'])->name('customers.destroyAdjustment');
        Route::get('/customers/{customer}/print', [\App\Http\Controllers\CustomerController::class, 'print'])->name('customers.print');
        
        Route::get('invoices/fake', [\App\Http\Controllers\InvoiceController::class, 'fake'])->name('invoices.fake');
        Route::post('invoices/fake/print', [\App\Http\Controllers\InvoiceController::class, 'printFake'])->name('invoices.printFake');
        
        Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
        Route::get('invoices/{invoice}/print', [\App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
        Route::post('invoices/{invoice}/payments', [\App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');
        Route::delete('payments/{payment}', [\App\Http\Controllers\PaymentController::class, 'destroy'])->name('payments.destroy');
        Route::get('customers/{customer}/balance', [\App\Http\Controllers\InvoiceController::class, 'customerBalance'])->name('customers.balance');

        // Expenses
        Route::get('expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
        Route::put('expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::get('expenses/print-report', [\App\Http\Controllers\ExpenseController::class, 'printReport'])->name('expenses.printReport');

        // Treasury (Cash Movement)
        Route::get('treasury', function() { return redirect()->route('expenses.index'); })->name('treasury.index');
        Route::post('treasury/deposit', [\App\Http\Controllers\TreasuryController::class, 'deposit'])->name('treasury.deposit');
        Route::post('treasury/withdraw', [\App\Http\Controllers\TreasuryController::class, 'withdraw'])->name('treasury.withdraw');
        Route::post('treasury/transfer', [\App\Http\Controllers\TreasuryController::class, 'transfer'])->name('treasury.transfer');
        Route::post('treasury/store', [\App\Http\Controllers\TreasuryController::class, 'store'])->name('treasury.store');
        Route::put('treasury/{id}', [\App\Http\Controllers\TreasuryController::class, 'update'])->name('treasury.update');
        Route::delete('treasury/{id}', [\App\Http\Controllers\TreasuryController::class, 'destroy'])->name('treasury.destroy');
        Route::get('treasury/print-ledger', [\App\Http\Controllers\TreasuryController::class, 'printLedger'])->name('treasury.printLedger');
        // Debts & Installments
        Route::resource('debts', \App\Http\Controllers\ExternalDebtController::class)->except(['create', 'edit']);
        Route::post('debts/{debt}/pay-installment/{installment}', [\App\Http\Controllers\ExternalDebtController::class, 'payInstallment'])->name('debts.payInstallment');
        Route::post('debts/{debt}/pay-partial', [\App\Http\Controllers\ExternalDebtController::class, 'payPartial'])->name('debts.payPartial');
        Route::post('debts/{debt}/pay-all', [\App\Http\Controllers\ExternalDebtController::class, 'payAll'])->name('debts.payAll');
        Route::get('debts/{debt}/print', [\App\Http\Controllers\ExternalDebtController::class, 'print'])->name('debts.print');

        // Unified Worker Financials (Advances, Penalties, Bonuses)
        Route::get('financials', [\App\Http\Controllers\WorkerFinancialController::class, 'index'])->name('financials.index');
        Route::post('financials', [\App\Http\Controllers\WorkerFinancialController::class, 'store'])->name('financials.store');
        Route::delete('financials/{id}', [\App\Http\Controllers\WorkerFinancialController::class, 'destroy'])->name('financials.destroy');

        // Worker Advances
        Route::get('advances', [\App\Http\Controllers\WorkerAdvanceController::class, 'index'])->name('advances.index');
        Route::post('advances', [\App\Http\Controllers\WorkerAdvanceController::class, 'store'])->name('advances.store');
        Route::delete('advances/{advance}', [\App\Http\Controllers\WorkerAdvanceController::class, 'destroy'])->name('advances.destroy');
        Route::get('advances/{advance}/print', [\App\Http\Controllers\WorkerAdvanceController::class, 'print'])->name('advances.print');

        // Worker Productions & Unified Attendance
        Route::resource('worker-productions', \App\Http\Controllers\WorkerProductionController::class)->except(['create', 'show', 'edit', 'update']);
        Route::post('attendance/unified', [\App\Http\Controllers\AttendanceController::class, 'storeUnified'])->name('attendance.unified.store');
        Route::get('attendance', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance', [\App\Http\Controllers\AttendanceController::class, 'store'])->name('attendance.store');
        Route::put('attendance/{attendance}', [\App\Http\Controllers\AttendanceController::class, 'update'])->name('attendance.update');

        // Salaries Financial (Admin/Cashier)
        Route::get('salaries/paid-report', [\App\Http\Controllers\SalaryController::class, 'printPaid'])->name('salaries.printPaid');
        Route::post('salaries/calculate', [\App\Http\Controllers\SalaryController::class, 'calculate'])->name('salaries.calculate');
        Route::post('salaries/pay', [\App\Http\Controllers\SalaryController::class, 'markAllAsPaid'])->name('salaries.pay_all');
        Route::put('salaries/{salary}/pay', [\App\Http\Controllers\SalaryController::class, 'markAsPaid'])->name('salaries.pay');
        Route::delete('salaries/{salary}/reverse', [\App\Http\Controllers\SalaryController::class, 'reversePayment'])->name('salaries.reverse');
        Route::get('salaries/{salary}/print', [\App\Http\Controllers\SalaryController::class, 'print'])->name('salaries.print');

        // Audit / Review & System Logs
        Route::get('audit', [\App\Http\Controllers\AuditController::class, 'index'])->name('audit.index');
        Route::get('system/logs', [\App\Http\Controllers\SystemLogController::class, 'index'])->name('system.logs');
        Route::post('system/logs/clear', [\App\Http\Controllers\SystemLogController::class, 'clearLogs'])->name('system.logs.clear');
        Route::post('system/fix', [\App\Http\Controllers\SystemLogController::class, 'fixSystem'])->name('system.fix');

        // Reports
        Route::get('reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/weekly-production', [\App\Http\Controllers\ReportController::class, 'weeklyProduction'])->name('reports.weekly-production');
        Route::get('reports/customer-invoices', [\App\Http\Controllers\ReportController::class, 'customerInvoices'])->name('reports.customer-invoices');
        
        // Search
        Route::get('search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');

        // Daily Custody Report (عهدة مندوب المبيعات اليومية)
        Route::get('daily-custody', [\App\Http\Controllers\DailyCustodyController::class, 'index'])->name('daily-custody.index');
        Route::post('daily-custody', [\App\Http\Controllers\DailyCustodyController::class, 'store'])->name('daily-custody.store');
        Route::get('daily-custody/{date}', [\App\Http\Controllers\DailyCustodyController::class, 'show'])->name('daily-custody.show')->where('date', '\d{4}-\d{2}-\d{2}');
        Route::post('daily-custody/{report}/entry', [\App\Http\Controllers\DailyCustodyController::class, 'addEntry'])->name('daily-custody.addEntry');
        Route::delete('daily-custody/entry/{entry}', [\App\Http\Controllers\DailyCustodyController::class, 'deleteEntry'])->name('daily-custody.deleteEntry');
        Route::get('daily-custody/{report}/print', [\App\Http\Controllers\DailyCustodyController::class, 'print'])->name('daily-custody.print');
    });



    // --- SUPERVISOR & HR & ADMIN SYSTEM ---
    Route::middleware(['role:Admin|Supervisor|HR'])->group(function () {
        // Workers Management (General info for Supervisors, full for Admins)
        Route::resource('workers', \App\Http\Controllers\WorkerController::class);
        Route::get('/workers/{worker}/statement', [\App\Http\Controllers\WorkerController::class, 'statement'])->name('workers.statement');
        Route::post('/workers/{worker}/adjust-balance', [\App\Http\Controllers\WorkerController::class, 'adjustBalance'])->name('workers.adjust_balance');
        Route::post('/workers/{worker}/reset-balance', [\App\Http\Controllers\WorkerController::class, 'resetBalance'])->name('workers.reset_balance');

        Route::get('supervisor/attendance', [\App\Http\Controllers\SupervisorAttendanceController::class, 'index'])->name('supervisor.attendance.index');
        Route::post('supervisor/attendance', [\App\Http\Controllers\SupervisorAttendanceController::class, 'store'])->name('supervisor.attendance.store');
        Route::post('supervisor/attendance/mark-all-absent', [\App\Http\Controllers\SupervisorAttendanceController::class, 'markAllAbsent'])->name('supervisor.attendance.markAllAbsent');
        // Production Routes
        Route::post('attendance/production', [\App\Http\Controllers\AttendanceController::class, 'storeProductionLogging'])->name('attendance.production.store');
        Route::get('production/daily', [\App\Http\Controllers\DailyDashboardController::class, 'index'])->name('production.daily');

        // Salaries & Weekly Attendance Grid (Shared with HR/Supervisor)
        Route::get('salaries', [\App\Http\Controllers\SalaryController::class, 'index'])->name('salaries.index');
        Route::post('salaries/recalculate', [\App\Http\Controllers\SalaryController::class, 'recalculate'])->name('salaries.recalculate');
        Route::post('salaries/update-day', [\App\Http\Controllers\SalaryController::class, 'updateDailyRecord'])->name('salaries.update-day');
        Route::post('salaries/clear-day-production', [\App\Http\Controllers\SalaryController::class, 'clearDayProduction'])->name('salaries.clear-day-production');
        Route::delete('salaries/production/{production}', [\App\Http\Controllers\SalaryController::class, 'deleteProduction'])->name('salaries.delete-production');
        Route::get('salaries/scissors-summary', [\App\Http\Controllers\SalaryController::class, 'scissorsSummary'])->name('salaries.scissors-summary');
    });

    // --- DRIVER & ADMIN SYSTEM ---
    Route::middleware(['role:Admin|Driver'])->group(function () {
        Route::get('driver', [\App\Http\Controllers\DriverController::class, 'index'])->name('driver.dashboard');
        Route::post('driver/shipments/{shipment}/start', [\App\Http\Controllers\DriverController::class, 'startTrip'])->name('driver.startTrip');
        Route::post('driver/shipments/{shipment}/end', [\App\Http\Controllers\DriverController::class, 'endTrip'])->name('driver.endTrip');
        Route::post('driver/shipments/{shipment}/location', [\App\Http\Controllers\DriverController::class, 'logLocation'])->name('driver.logLocation');
    });

    // --- LOADER & ADMIN SYSTEM ---
    Route::middleware(['role:Admin|Loader'])->group(function () {
        Route::get('loading', [\App\Http\Controllers\LoadingController::class, 'index'])->name('loading.index');
        Route::get('loading/create-adhoc', [\App\Http\Controllers\LoadingController::class, 'createAdhoc'])->name('loading.create-adhoc');
        Route::post('loading/store-adhoc', [\App\Http\Controllers\LoadingController::class, 'storeAdhoc'])->name('loading.store-adhoc');
        Route::post('loading/save-progress', [\App\Http\Controllers\LoadingController::class, 'saveProgress'])->name('loading.save-progress');
        Route::get('loading/{order}', [\App\Http\Controllers\LoadingController::class, 'show'])->name('loading.show');
        Route::post('loading/{order}/confirm', [\App\Http\Controllers\LoadingController::class, 'confirm'])->name('loading.confirm');
        Route::post('loading/{order}/cancel', [\App\Http\Controllers\LoadingController::class, 'cancel'])->name('loading.cancel');
    });

    // --- WORKSHOPS SYSTEM ---
    Route::middleware(['role:Admin'])->group(function () {
        Route::resource('workshops', \App\Http\Controllers\WorkshopController::class)->except(['create', 'edit', 'destroy']);
        // Workshop Transactions (Payments)
        Route::post('workshops/{workshop}/transactions', [\App\Http\Controllers\WorkshopController::class, 'storeTransaction'])->name('workshops.transactions.store');
        Route::put('workshops/transactions/{transaction}', [\App\Http\Controllers\WorkshopController::class, 'updateTransaction'])->name('workshops.transactions.update');
        Route::delete('workshops/transactions/{transaction}', [\App\Http\Controllers\WorkshopController::class, 'destroyTransaction'])->name('workshops.transactions.destroy');
        Route::get('workshops/{workshop}/print', [\App\Http\Controllers\WorkshopController::class, 'print'])->name('workshops.print');
        // Workshop Invoices
        Route::get('workshops/invoices/create', [\App\Http\Controllers\WorkshopInvoiceController::class, 'create'])->name('workshops.invoices.create');
        Route::post('workshops/invoices', [\App\Http\Controllers\WorkshopInvoiceController::class, 'store'])->name('workshops.invoices.store');
        Route::get('workshops/invoices/{invoice}', [\App\Http\Controllers\WorkshopInvoiceController::class, 'show'])->name('workshops.invoices.show');
        Route::get('workshops/invoices/{invoice}/print', [\App\Http\Controllers\WorkshopInvoiceController::class, 'print'])->name('workshops.invoices.print');
        Route::get('workshops/invoices/{invoice}/edit', [\App\Http\Controllers\WorkshopInvoiceController::class, 'edit'])->name('workshops.invoices.edit');
        Route::put('workshops/invoices/{invoice}', [\App\Http\Controllers\WorkshopInvoiceController::class, 'update'])->name('workshops.invoices.update');
        Route::delete('workshops/invoices/{invoice}', [\App\Http\Controllers\WorkshopInvoiceController::class, 'destroy'])->name('workshops.invoices.destroy');
    });

    // System Direct Print
    Route::post('/system/direct-print', [\App\Http\Controllers\DirectPrintController::class, 'print'])->name('system.direct-print');

    // --- WHATSAPP-STYLE ADMIN CHAT SYSTEM ---
    Route::get('/chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages', [\App\Http\Controllers\ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/chat/send', [\App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/mark-read', [\App\Http\Controllers\ChatController::class, 'markAsRead'])->name('chat.mark-read');
    Route::get('/chat/unread-count', [\App\Http\Controllers\ChatController::class, 'getUnreadCount'])->name('chat.unread-count');
    Route::delete('/chat/messages/{message}', [\App\Http\Controllers\ChatController::class, 'destroy'])->name('chat.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
