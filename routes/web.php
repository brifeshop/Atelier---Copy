<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\EmployeeController;
use App\Http\Controllers\Master\MachineController;
use App\Http\Controllers\Master\MaterialController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\WorkCenterController;
use App\Http\Controllers\Master\MaterialImportController;

use App\Http\Controllers\Sales\SalesOrderController;
use App\Http\Controllers\Sales\DeliveryOrderController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\SalesReturnController;

use App\Http\Controllers\Warehouse\WarehouseController;
use App\Http\Controllers\Warehouse\LocationController;
use App\Http\Controllers\Warehouse\InventoryController;
use App\Http\Controllers\Warehouse\StockMovementController;
use App\Http\Controllers\Warehouse\StockOpnameController;
use App\Http\Controllers\Warehouse\StockCardController;
use App\Http\Controllers\Warehouse\LowStockController;

use App\Http\Controllers\Purchasing\PurchaseRequisitionController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\GoodsReceiptController;
use App\Http\Controllers\Purchasing\SupplierPriceController;
use App\Http\Controllers\Purchasing\PurchaseReturnController;
use App\Http\Controllers\Purchasing\PurchasingDashboardController;
use App\Http\Controllers\Purchasing\SupplierEvaluationController;

use App\Http\Controllers\Engineering\BomController;
use App\Http\Controllers\Engineering\RoutingController;
use App\Http\Controllers\Engineering\BomImportController;

use App\Http\Controllers\Production\WorkOrderController;
use App\Http\Controllers\Production\CostingController;
use App\Http\Controllers\Production\MaterialIssueController;
use App\Http\Controllers\Production\WorkOrderProgressController;
use App\Http\Controllers\Production\FinishedGoodsReceiptController;

use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\SystemSettingsController;
use App\Http\Controllers\Settings\AuditLogController;

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ==================== MASTER DATA ====================
Route::middleware(['auth', 'verified'])->prefix('master')->name('master.')->group(function () {
    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('machines', MachineController::class);

    Route::get('products/bulk', [ProductController::class, 'bulkForm'])->name('products.bulk');
    Route::post('products/bulk-store', [ProductController::class, 'bulkStore'])->name('products.bulk-store');

    Route::get('work-centers/bulk', [WorkCenterController::class, 'bulkForm'])->name('work-centers.bulk');
    Route::post('work-centers/bulk-store', [WorkCenterController::class, 'bulkStore'])->name('work-centers.bulk-store');
    Route::resource('work-centers', WorkCenterController::class);

    Route::prefix('materials')->name('materials.')->group(function () {
        Route::get('import', [MaterialImportController::class, 'index'])->name('import');
        Route::get('import/template', [MaterialImportController::class, 'template'])->name('import.template');
        Route::post('import/preview', [MaterialImportController::class, 'preview'])->name('import.preview');
        Route::post('import/store', [MaterialImportController::class, 'store'])->name('import.store');
        Route::get('bulk', [MaterialController::class, 'bulkForm'])->name('bulk');
        Route::post('bulk-store', [MaterialController::class, 'bulkStore'])->name('bulk-store');
        Route::post('quick-store', [MaterialController::class, 'quickStore'])->name('quick-store');
    });
    Route::resource('materials', MaterialController::class);
    Route::resource('products', ProductController::class);
});

// ==================== SALES ====================
Route::middleware(['auth', 'verified'])->prefix('sales')->name('sales.')->group(function () {

    // ---------- SALES ORDER ----------
    Route::resource('sales-orders', SalesOrderController::class);
    Route::patch('sales-orders/{sales_order}/status', [SalesOrderController::class, 'updateStatus'])
        ->name('sales-orders.status');

        // ---------- SALES RETURN ----------
    Route::prefix('returns')->name('returns.')->group(function () {
        Route::get('/', [SalesReturnController::class, 'index'])->name('index');
        Route::get('/create', [SalesReturnController::class, 'create'])->name('create');
        Route::post('/', [SalesReturnController::class, 'store'])->name('store');
        Route::get('/{return}', [SalesReturnController::class, 'show'])->name('show');
        Route::patch('/{return}/complete', [SalesReturnController::class, 'complete'])->name('complete');
        Route::patch('/{return}/cancel', [SalesReturnController::class, 'cancel'])->name('cancel');
        
        // API untuk ambil item DO
        Route::get('/delivery-order/{deliveryOrder}', [SalesReturnController::class, 'getDeliveryOrderDetails'])->name('do-details');
    });

    // ---------- DELIVERY ORDER ----------
    Route::prefix('delivery-orders')->name('delivery-orders.')->group(function () {
        Route::get('/', [DeliveryOrderController::class, 'index'])
            ->middleware('can:view delivery-orders')
            ->name('index');

        Route::get('/create', [DeliveryOrderController::class, 'create'])
            ->middleware('can:create delivery-orders')
            ->name('create');

        Route::post('/', [DeliveryOrderController::class, 'store'])
            ->middleware('can:create delivery-orders')
            ->name('store');

        // AJAX endpoints — HARUS SEBELUM {deliveryOrder}
        Route::get('/sales-order/{salesOrder}', [DeliveryOrderController::class, 'getSalesOrderDetails'])
            ->middleware('can:view delivery-orders')
            ->name('sales-order-details');

        Route::get('/warehouse/{warehouse}/locations', [DeliveryOrderController::class, 'getLocations'])
            ->middleware('can:view delivery-orders')
            ->name('warehouse-locations');

        Route::get('/check-stock', [DeliveryOrderController::class, 'checkStock'])
            ->middleware('can:view delivery-orders')
            ->name('check-stock');

        // Detail routes
        Route::get('/{deliveryOrder}', [DeliveryOrderController::class, 'show'])
            ->middleware('can:view delivery-orders')
            ->name('show');

        Route::get('/{deliveryOrder}/edit', [DeliveryOrderController::class, 'edit'])
            ->middleware('can:edit delivery-orders')
            ->name('edit');

        Route::put('/{deliveryOrder}', [DeliveryOrderController::class, 'update'])
            ->middleware('can:edit delivery-orders')
            ->name('update');

        Route::delete('/{deliveryOrder}', [DeliveryOrderController::class, 'destroy'])
            ->middleware('can:delete delivery-orders')
            ->name('destroy');

        // Custom actions
        Route::patch('/{deliveryOrder}/mark-ready', [DeliveryOrderController::class, 'markReady'])
            ->middleware('can:edit delivery-orders')
            ->name('mark-ready');

        Route::patch('/{deliveryOrder}/ship', [DeliveryOrderController::class, 'ship'])
            ->middleware('can:ship delivery-orders')
            ->name('ship');

        Route::patch('/{deliveryOrder}/deliver', [DeliveryOrderController::class, 'deliver'])
            ->middleware('can:deliver delivery-orders')
            ->name('deliver');

        Route::patch('/{deliveryOrder}/cancel', [DeliveryOrderController::class, 'cancel'])
            ->middleware('can:edit delivery-orders')
            ->name('cancel');
    });
        // ---------- SALES INVOICE ----------
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [SalesInvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}', [SalesInvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/print', [SalesInvoiceController::class, 'print'])->name('print');
        Route::patch('/{invoice}/mark-paid', [SalesInvoiceController::class, 'markPaid'])->name('mark-paid');
        Route::patch('/{invoice}/cancel', [SalesInvoiceController::class, 'cancel'])->name('cancel');
    });

    // Route untuk membuat Invoice dari DO
    Route::post('delivery-orders/{deliveryOrder}/invoice', [SalesInvoiceController::class, 'store'])
        ->name('delivery-orders.generate-invoice');
});

// ==================== WAREHOUSE ====================
Route::middleware(['auth', 'verified'])->prefix('warehouse')->name('warehouse.')->group(function () {
    Route::resource('warehouses', WarehouseController::class);

    Route::get('locations/bulk', [LocationController::class, 'bulkForm'])->name('locations.bulk');
    Route::post('locations/bulk-store', [LocationController::class, 'bulkStore'])->name('locations.bulk-store');
    Route::resource('locations', LocationController::class);

    Route::resource('inventories', InventoryController::class)->only(['index', 'show', 'update']);

    Route::get('stock-movements/adjustments', [StockMovementController::class, 'adjustments'])->name('stock-movements.adjustments');
    Route::get('stock-movements/get-stock', [StockMovementController::class, 'getStock'])->name('stock-movements.get-stock');
    Route::get('stock-movements/get-locations', [StockMovementController::class, 'getLocations'])->name('stock-movements.get-locations');
    Route::get('stock-movements/transfer/create', [StockMovementController::class, 'createTransfer'])->name('stock-movements.transfer.create');
    Route::post('stock-movements/transfer', [StockMovementController::class, 'storeTransfer'])->name('stock-movements.transfer.store');
    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'create', 'store', 'show']);

    Route::resource('stock-opnames', StockOpnameController::class);
    Route::patch('stock-opnames/{stock_opname}/approve', [StockOpnameController::class, 'approve'])->name('stock-opnames.approve');
    Route::patch('stock-opnames/{stock_opname}/cancel', [StockOpnameController::class, 'cancel'])->name('stock-opnames.cancel');
    Route::patch('stock-opnames/{stock_opname}/items/{item}', [StockOpnameController::class, 'updateItem'])->name('stock-opnames.items.update');

    Route::get('stock-cards', [StockCardController::class, 'index'])->name('stock-cards.index');
    Route::get('low-stocks', [LowStockController::class, 'index'])->name('low-stocks.index');
});

// ==================== PURCHASING ====================
Route::middleware(['auth', 'verified'])->prefix('purchasing')->name('purchasing.')->group(function () {
    Route::get('dashboard', [PurchasingDashboardController::class, 'index'])->name('dashboard');

    Route::resource('purchase-requisitions', PurchaseRequisitionController::class);
    Route::patch('purchase-requisitions/{purchase_requisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('purchase-requisitions.approve');
    Route::patch('purchase-requisitions/{purchase_requisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('purchase-requisitions.reject');

    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::patch('purchase-orders/{purchase_order}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::patch('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    Route::resource('goods-receipts', GoodsReceiptController::class);
    Route::patch('goods-receipts/{goods_receipt}/receive', [GoodsReceiptController::class, 'receive'])->name('goods-receipts.receive');

    Route::resource('purchase-returns', PurchaseReturnController::class);
    Route::patch('purchase-returns/{purchase_return}/complete', [PurchaseReturnController::class, 'complete'])->name('purchase-returns.complete');

    Route::resource('supplier-prices', SupplierPriceController::class)->except(['show']);
    Route::get('supplier-prices-compare', [SupplierPriceController::class, 'compare'])->name('supplier-prices.compare');
    Route::get('api/supplier-price', [SupplierPriceController::class, 'getPrice'])->name('api.supplier-price');

    Route::resource('supplier-evaluations', SupplierEvaluationController::class);

        // QC Inspection
    Route::get('qc-inspections', [\App\Http\Controllers\Purchasing\QcInspectionController::class, 'index'])->name('qc-inspections.index');
    Route::get('qc-inspections/{qcInspection}', [\App\Http\Controllers\Purchasing\QcInspectionController::class, 'show'])->name('qc-inspections.show');
    Route::patch('qc-inspections/{qcInspection}/complete', [\App\Http\Controllers\Purchasing\QcInspectionController::class, 'complete'])->name('qc-inspections.complete');
});

// ==================== NOTIFICATIONS ====================
Route::middleware(['auth'])->group(function () {
    Route::patch('notifications/read-all', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    })->name('notifications.read-all');
});

// ==================== ENGINEERING ====================
Route::middleware(['auth', 'verified'])->prefix('engineering')->name('engineering.')->group(function () {
    Route::get('boms/{bom}/import', [BomImportController::class, 'index'])->name('boms.import');
    Route::get('boms/{bom}/import/template', [BomImportController::class, 'template'])->name('boms.import.template');
    Route::post('boms/{bom}/import/preview', [BomImportController::class, 'preview'])->name('boms.import.preview');
    Route::post('boms/{bom}/import/store', [BomImportController::class, 'store'])->name('boms.import.store');

    Route::resource('boms', BomController::class);
    Route::patch('boms/{bom}/activate', [BomController::class, 'activate'])->name('boms.activate');
    Route::patch('boms/{bom}/obsolete', [BomController::class, 'obsolete'])->name('boms.obsolete');
    Route::post('boms/{bom}/recalculate', [BomController::class, 'recalculate'])->name('boms.recalculate');
    Route::post('boms/{bom}/items', [BomController::class, 'addItem'])->name('boms.items.store');
    Route::patch('boms/{bom}/items/{item}', [BomController::class, 'updateItem'])->name('boms.items.update');
    Route::delete('boms/{bom}/items/{item}', [BomController::class, 'removeItem'])->name('boms.items.destroy');
    Route::post('boms/{bom}/headers', [BomController::class, 'addHeader'])->name('boms.headers.store');

    Route::resource('routings', RoutingController::class);
    Route::patch('routings/{routing}/activate', [RoutingController::class, 'activate'])->name('routings.activate');
    Route::patch('routings/{routing}/obsolete', [RoutingController::class, 'obsolete'])->name('routings.obsolete');
    Route::post('routings/{routing}/recalculate', [RoutingController::class, 'recalculate'])->name('routings.recalculate');
    Route::post('routings/{routing}/steps', [RoutingController::class, 'addStep'])->name('routings.steps.store');
    Route::patch('routings/{routing}/steps/{step}', [RoutingController::class, 'updateStep'])->name('routings.steps.update');
    Route::delete('routings/{routing}/steps/{step}', [RoutingController::class, 'removeStep'])->name('routings.steps.destroy');
});

// ==================== PRODUCTION ====================
Route::middleware(['auth', 'verified'])->prefix('production')->name('production.')->group(function () {
    Route::resource('work-orders', WorkOrderController::class);
    Route::patch('work-orders/{work_order}/release', [WorkOrderController::class, 'release'])->name('work-orders.release');
    Route::patch('work-orders/{work_order}/start', [WorkOrderController::class, 'start'])->name('work-orders.start');
    Route::patch('work-orders/{work_order}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete');
    Route::patch('work-orders/{work_order}/cancel', [WorkOrderController::class, 'cancel'])->name('work-orders.cancel');
    Route::post('work-orders/{work_order}/recalculate', [WorkOrderController::class, 'recalculate'])->name('work-orders.recalculate');
    Route::post('work-orders/{work_order}/snapshot', [WorkOrderController::class, 'snapshot'])->name('work-orders.snapshot');

    Route::get('work-orders/{work_order}/progress', [WorkOrderProgressController::class, 'index'])->name('work-orders.progress');
    Route::patch('work-orders/{work_order}/progress/bulk', [WorkOrderProgressController::class, 'bulkUpdate'])->name('work-orders.progress.bulk');
    Route::patch('work-orders/{work_order}/progress/{progress}', [WorkOrderProgressController::class, 'update'])->name('work-orders.progress.update');
    Route::patch('work-orders/{work_order}/progress/{progress}/start', [WorkOrderProgressController::class, 'start'])->name('work-orders.progress.start');
    Route::patch('work-orders/{work_order}/progress/{progress}/complete', [WorkOrderProgressController::class, 'complete'])->name('work-orders.progress.complete');

    Route::get('work-orders/{work_order}/materials', [MaterialIssueController::class, 'getWorkOrderMaterials'])->name('work-orders.materials');

    Route::get('material-issues/get-locations', [MaterialIssueController::class, 'getMaterialLocations'])->name('material-issues.get-locations');
    Route::patch('material-issues/{material_issue}/issue', [MaterialIssueController::class, 'issue'])->name('material-issues.issue');
    Route::resource('material-issues', MaterialIssueController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::get('finished-goods/get-locations/{warehouse}', [FinishedGoodsReceiptController::class, 'getLocations'])->name('finished-goods.get-locations');
    Route::patch('finished-goods/{finished_good}/receive', [FinishedGoodsReceiptController::class, 'receive'])->name('finished-goods.receive');
    Route::resource('finished-goods', FinishedGoodsReceiptController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::get('costing', [CostingController::class, 'index'])->name('costing.index');
    Route::get('costing/variance', [CostingController::class, 'variance'])->name('costing.variance');
    Route::get('costing/margin', [CostingController::class, 'margin'])->name('costing.margin');
    Route::get('costing/{cost_snapshot}', [CostingController::class, 'show'])->name('costing.show');
    Route::get('costing-export', [CostingController::class, 'export'])->name('costing.export');
});

// ==================== SETTINGS ====================
// Users & Roles — akses: manage roles
Route::middleware(['auth', 'can:manage roles'])
    ->prefix('settings')
    ->name('settings.')
    ->group(function () {
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::resource('roles', RoleController::class);
    });

// System Config & Audit — akses: manage settings
Route::middleware(['auth', 'can:manage settings'])
    ->prefix('settings')
    ->name('settings.')
    ->group(function () {
        Route::get('system/profile', [SystemSettingsController::class, 'profile'])->name('system.profile');
        Route::post('system/profile', [SystemSettingsController::class, 'updateProfile'])->name('system.profile.update');
        Route::get('system/preferences', [SystemSettingsController::class, 'preferences'])->name('system.preferences');
        Route::post('system/preferences', [SystemSettingsController::class, 'updatePreferences'])->name('system.preferences.update');

        Route::get('audit/logs', [AuditLogController::class, 'index'])->name('audit.logs');
    });

require __DIR__.'/auth.php';
require __DIR__.'/finance.php';