<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryCountController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\RepairOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| الصفحة الرئيسية
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : Inertia::render('Welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| لوحة التحكم
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| المصادقة والملف الشخصي
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // الملف الشخصي
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    /*
    |--------------------------------------------------------------------------
    | الفئات
    |--------------------------------------------------------------------------
    */
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | المنتجات
    |--------------------------------------------------------------------------
    */
    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | إدارة المخزون
    |--------------------------------------------------------------------------
    */
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    Route::post('/inventory/add-stock', [InventoryController::class, 'addStock'])->name('inventory.add-stock');
    Route::post('/inventory/deduct-stock', [InventoryController::class, 'deductStock'])->name('inventory.deduct-stock');
    Route::post('/inventory/transfer-stock', [InventoryController::class, 'transferStock'])->name('inventory.transfer-stock');

    /*
    |--------------------------------------------------------------------------
    | الجرد المخزني
    |--------------------------------------------------------------------------
    */
    Route::get('/inventory-counts', [InventoryCountController::class, 'index'])->name('inventory-counts.index');
    Route::get('/inventory-counts/create', [InventoryCountController::class, 'create'])->name('inventory-counts.create');
    Route::post('/inventory-counts', [InventoryCountController::class, 'store'])->name('inventory-counts.store');
    Route::get('/inventory-counts/{inventoryCount}', [InventoryCountController::class, 'show'])->name('inventory-counts.show');
    Route::patch('/inventory-counts/items/{item}', [InventoryCountController::class, 'updateItem'])->name('inventory-counts.update-item');
    Route::post('/inventory-counts/{inventoryCount}/complete', [InventoryCountController::class, 'complete'])->name('inventory-counts.complete');
    Route::post('/inventory-counts/{inventoryCount}/cancel', [InventoryCountController::class, 'cancel'])->name('inventory-counts.cancel');

    /*
    |--------------------------------------------------------------------------
    | الموردون
    |--------------------------------------------------------------------------
    */
    Route::resource('suppliers', SupplierController::class)->except(['show']);
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | العملاء
    |--------------------------------------------------------------------------
    */
    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | المشتريات
    |--------------------------------------------------------------------------
    */
    Route::get('/purchases', [PurchaseInvoiceController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseInvoiceController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseInvoiceController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{purchaseInvoice}', [PurchaseInvoiceController::class, 'show'])->name('purchases.show');
    Route::get('/purchases/{purchaseInvoice}/edit', [PurchaseInvoiceController::class, 'edit'])->name('purchases.edit');
    Route::put('/purchases/{purchaseInvoice}', [PurchaseInvoiceController::class, 'update'])->name('purchases.update');
    Route::post('/purchases/{purchaseInvoice}/approve', [PurchaseInvoiceController::class, 'approve'])->name('purchases.approve');
    Route::post('/purchases/{purchaseInvoice}/payments', [PurchaseInvoiceController::class, 'addPayment'])->name('purchases.add-payment');
    Route::post('/purchases/{purchaseInvoice}/cancel', [PurchaseInvoiceController::class, 'cancel'])->name('purchases.cancel');

    /*
    |--------------------------------------------------------------------------
    | نقطة البيع وفواتير المبيعات
    |--------------------------------------------------------------------------
    */
    Route::get('/pos', [SalesInvoiceController::class, 'pos'])->name('pos');
    Route::get('/sales', [SalesInvoiceController::class, 'index'])->name('sales.index');
    Route::post('/sales', [SalesInvoiceController::class, 'store'])->name('sales.store');
    Route::get('/sales/{salesInvoice}', [SalesInvoiceController::class, 'show'])->name('sales.show');
    Route::get('/sales/{salesInvoice}/edit', [SalesInvoiceController::class, 'edit'])->name('sales.edit');
    Route::put('/sales/{salesInvoice}', [SalesInvoiceController::class, 'update'])->name('sales.update');
    Route::post('/sales/{salesInvoice}/approve', [SalesInvoiceController::class, 'approve'])->name('sales.approve');
    Route::post('/sales/{salesInvoice}/payments', [SalesInvoiceController::class, 'addPayment'])->name('sales.add-payment');
    Route::post('/sales/{salesInvoice}/cancel', [SalesInvoiceController::class, 'cancel'])->name('sales.cancel');
    Route::get('/sales/{salesInvoice}/print', [SalesInvoiceController::class, 'print'])->name('sales.print');

    /*
    |--------------------------------------------------------------------------
    | الصيانة
    |--------------------------------------------------------------------------
    */
    Route::get('/repairs', [RepairOrderController::class, 'index'])->name('repairs.index');
    Route::get('/repairs/create', [RepairOrderController::class, 'create'])->name('repairs.create');
    Route::post('/repairs', [RepairOrderController::class, 'store'])->name('repairs.store');
    Route::get('/repairs/quick-create', [RepairOrderController::class, 'quickCreate'])->name('repairs.quick-create');
    Route::post('/repairs/quick', [RepairOrderController::class, 'quickStore'])->name('repairs.quick-store');
    Route::get('/repairs/{repairOrder}', [RepairOrderController::class, 'show'])->name('repairs.show');
    Route::post('/repairs/{repairOrder}/inspection', [RepairOrderController::class, 'updateInspection'])->name('repairs.inspection');
    Route::post('/repairs/{repairOrder}/parts', [RepairOrderController::class, 'addPart'])->name('repairs.add-part');
    Route::delete('/repairs/parts/{part}', [RepairOrderController::class, 'removePart'])->name('repairs.remove-part');
    Route::post('/repairs/{repairOrder}/parts/commit', [RepairOrderController::class, 'commitParts'])->name('repairs.commit-parts');
    Route::post('/repairs/parts/{part}/revert', [RepairOrderController::class, 'revertPart'])->name('repairs.revert-part');
    Route::post('/repairs/{repairOrder}/status', [RepairOrderController::class, 'updateStatus'])->name('repairs.update-status');
    Route::post('/repairs/{repairOrder}/ready', [RepairOrderController::class, 'markAsReady'])->name('repairs.mark-ready');
    Route::post('/repairs/{repairOrder}/deliver', [RepairOrderController::class, 'deliver'])->name('repairs.deliver');
    Route::post('/repairs/{repairOrder}/payments', [RepairOrderController::class, 'addPayment'])->name('repairs.add-payment');
    Route::post('/repairs/{repairOrder}/cancel', [RepairOrderController::class, 'cancel'])->name('repairs.cancel');
    Route::get('/repairs/{repairOrder}/print', [RepairOrderController::class, 'print'])->name('repairs.print');

    /*
    |--------------------------------------------------------------------------
    | الدفعات والديون
    |--------------------------------------------------------------------------
    */
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/customer-receivables', [PaymentController::class, 'customerReceivables'])->name('payments.customer-receivables');
    Route::get('/payments/supplier-payables', [PaymentController::class, 'supplierPayables'])->name('payments.supplier-payables');
    Route::get('/payments/all-payments', [PaymentController::class, 'allPayments'])->name('payments.all-payments');
    Route::post('/payments', [PaymentController::class, 'storePayment'])->name('payments.store');
    Route::get('/customers/{customer}/statement', [PaymentController::class, 'customerStatement'])->name('customers.statement');
    Route::get('/suppliers/{supplier}/statement', [PaymentController::class, 'supplierStatement'])->name('suppliers.statement');

    /*
    |--------------------------------------------------------------------------
    | المركز المالي والمصروفات
    |--------------------------------------------------------------------------
    */
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/accounts', [FinanceController::class, 'storeAccount'])->name('finance.store-account');
    Route::patch('/finance/accounts/{financialAccount}/status', [FinanceController::class, 'toggleAccountStatus'])->name('finance.toggle-account');
    Route::get('/finance/accounts/{financialAccount}/print', [FinanceController::class, 'printAccount'])->name('finance.print-account');
    Route::get('/finance/accounts/{financialAccount}', [FinanceController::class, 'showAccount'])->name('finance.show-account');
    Route::post('/finance/sync-payments', [FinanceController::class, 'syncPayments'])->name('finance.sync-payments');
    Route::get('/finance/expenses', [FinanceController::class, 'expenses'])->name('finance.expenses');
    Route::post('/finance/expenses', [FinanceController::class, 'storeExpense'])->name('finance.store-expense');
    Route::post('/finance/expenses/{expense}/cancel', [FinanceController::class, 'cancelExpense'])->name('finance.cancel-expense');
    Route::post('/finance/manual-transaction', [FinanceController::class, 'manualTransaction'])->name('finance.manual-transaction');
    Route::get('/finance/closings', [FinanceController::class, 'closings'])->name('finance.closings');
    Route::post('/finance/closings', [FinanceController::class, 'closing'])->name('finance.closing');

    /*
    |--------------------------------------------------------------------------
    | المرتجعات والاستبدال
    |--------------------------------------------------------------------------
    */
    Route::get('/returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::get('/returns/create', [ReturnController::class, 'createReturn'])->name('returns.create');
    Route::get('/sales-returns/create/{invoiceId}', [ReturnController::class, 'createSalesReturn'])->name('returns.create-sales');
    Route::post('/sales-returns', [ReturnController::class, 'storeSalesReturn'])->name('returns.store-sales');
    Route::post('/sales-returns/{salesReturn}/approve', [ReturnController::class, 'approveSalesReturn'])->name('returns.approve-sales');
    Route::post('/sales-returns/{salesReturn}/cancel', [ReturnController::class, 'cancelSalesReturn'])->name('returns.cancel-sales');
    Route::get('/sales-returns/{salesReturn}/print', [ReturnController::class, 'printSalesReturn'])->name('returns.print-sales');
    Route::get('/purchase-returns/create/{invoiceId}', [ReturnController::class, 'createPurchaseReturn'])->name('returns.create-purchase');
    Route::post('/purchase-returns', [ReturnController::class, 'storePurchaseReturn'])->name('returns.store-purchase');
    Route::post('/purchase-returns/{purchaseReturn}/approve', [ReturnController::class, 'approvePurchaseReturn'])->name('returns.approve-purchase');
    Route::post('/purchase-returns/{purchaseReturn}/cancel', [ReturnController::class, 'cancelPurchaseReturn'])->name('returns.cancel-purchase');
    Route::get('/purchase-returns/{purchaseReturn}/print', [ReturnController::class, 'printPurchaseReturn'])->name('returns.print-purchase');
    Route::get('/exchanges/create', [ReturnController::class, 'createExchange'])->name('returns.create-exchange');
    Route::post('/exchanges', [ReturnController::class, 'storeExchange'])->name('returns.store-exchange');
    Route::get('/exchanges/{exchange}/print', [ReturnController::class, 'printExchange'])->name('returns.print-exchange');

    /*
    |--------------------------------------------------------------------------
    | التقارير
    |--------------------------------------------------------------------------
    */
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('/reports/repairs', [ReportController::class, 'repairs'])->name('reports.repairs');
    Route::get('/reports/finance', [ReportController::class, 'finance'])->name('reports.finance');
    Route::get('/reports/returns', [ReportController::class, 'returns'])->name('reports.returns');

    // التصدير إلى PDF وExcel
    // أسماء متوافقة مع الواجهات الحالية
    foreach (['sales', 'purchases', 'inventory', 'repairs', 'finance', 'returns'] as $reportType) {
        Route::get("/reports/{$reportType}/pdf", [ReportController::class, 'exportPdf'])
            ->defaults('type', $reportType)
            ->name("reports.{$reportType}.pdf");
        Route::get("/reports/{$reportType}/excel", [ReportController::class, 'exportExcel'])
            ->defaults('type', $reportType)
            ->name("reports.{$reportType}.excel");
    }

    // نسخة الطباعة
    Route::get('/reports/print', [ReportController::class, 'print'])
        ->name('reports.print');

    /*
    |--------------------------------------------------------------------------
    | الإعدادات والنسخ الاحتياطي
    |--------------------------------------------------------------------------
    */
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Backup Routes
    Route::post('/settings/backup/create', [SettingsController::class, 'createBackup'])->name('settings.backup.create');
    Route::get('/settings/backup/download/{filename}', [SettingsController::class, 'downloadBackup'])->name('settings.backup.download');
    Route::delete('/settings/backup/delete/{filename}', [SettingsController::class, 'deleteBackup'])->name('settings.backup.delete');
    Route::post('/settings/backup/clean', [SettingsController::class, 'cleanBackups'])->name('settings.backup.clean');
    Route::get('/settings/backup/status', [SettingsController::class, 'getBackupStatus'])->name('settings.backup.status');
});

/*
|--------------------------------------------------------------------------
| منع التسجيل العام
|--------------------------------------------------------------------------
*/
Route::get('/register', function () {
    abort(404);
})->name('register');

Route::post('/register', function () {
    abort(404);
});

/*
|--------------------------------------------------------------------------
| ملف المصادقة
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
