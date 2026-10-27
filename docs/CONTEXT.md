# Atelier ERP - Project Context & Handover

Generated: 2026-10-26 20:19:13

## 1. Tech Stack
- Framework: Laravel 13 (PHP 8.3)
- Database: SQLite (untuk development)
- Frontend: Blade + Tailwind CSS + Alpine.js
- Library: Spatie Permission (Role & Permission), Spatie Activity Log
- Design System: Navy-Gold theme (Custom komponen <x-atelier.*>)

## 2. Status Milestone
- [x] FASE 0: Persiapan
- [x] FASE 1: Modul Sales (Sales Order, Delivery Order, Invoice, Sales Return)
- [x] FASE 4: Settings (User, Role, System, Audit Log, Middleware is_active)
- [ ] FASE 2: Finance (AR/AP, Jurnal, Kas) <- NEXT
- [ ] FASE 3: Quality Control
- [ ] FASE 5: Reporting
- [ ] FASE 6: Dashboard per Role

## 3. Pola Kode Penting (WAJIB DIBACA AI)
- **Service Pattern**: Logika bisnis diletakkan di pp/Services/. Method menggunakan static.
- **Stock Movement**: Setiap perubahan stok wajib lewat StockMovementService atau mencatat ke tabel stock_movements dengan format SM-YYYY-XXXX.
- **Generasi Nomor**: SO (SO-YYYY-XXXX), DO (DO-YYYYMMDD-XXXX), Invoice (INV-YYYYMM-XXXX), Retur (SR-YYYYMM-XXXX).
- **Alpine.js**: Untuk form dinamis (seperti DO & Retur), gunakan <select> native dengan x-model alih-alih komponen searchable yang rumit untuk menghindari bug reaktivitas.
- **Routing**: Gunakan url('path/...') alih-alih oute('name') jika ada masalah *Route Not Found* saat update status.

## 4. Instruksi untuk AI Berikutnya
Kita akan memulai **FASE 2 (Finance Dasar)**. Rencana untuk FASE 2:
1. **Chart of Accounts (COA)**: Tabel chart_of_accounts.
2. **Journal Entries**: Tabel journals dan journal_items (double-entry).
3. **Accounts Receivable (AR)**: Otomatis dibuat dari Sales Invoice yang statusnya Unpaid.
4. **Accounts Payable (AP)**: Otomatis dibuat dari Goods Receipt (Pembelian).
5. **Payments**: Form untuk mencatat pembayaran customer (melunasi Invoice).

Tolong mulai dengan merancang struktur tabel dan Service untuk Finance.

## 5. Resume Database (Migrations)
```
0001_01_01_000000_create_users_table.php
0001_01_01_000001_create_cache_table.php
0001_01_01_000002_create_jobs_table.php
2026_10_01_071113_create_permission_tables.php
2026_10_01_084208_create_customers_table.php
2026_10_01_091455_create_suppliers_table.php
2026_10_02_012419_create_employees_table.php
2026_10_02_035110_create_machines_table.php
2026_10_02_082932_create_materials_table.php
2026_10_02_090359_create_products_table.php
2026_10_02_091507_create_work_centers_table.php
2026_10_02_093840_create_sales_orders_table.php
2026_10_02_093841_create_sales_order_items_table.php
2026_10_05_062210_create_sales_order_status_logs_table.php
2026_10_05_065514_add_photo_path_to_materials_and_products_table.php
2026_10_05_074935_create_warehouses_table.php
2026_10_05_081011_create_locations_table.php
2026_10_05_082330_create_inventories_table.php
2026_10_05_084350_create_stock_movements_table.php
2026_10_06_025945_create_purchase_requisitions_table.php
2026_10_06_025946_create_purchase_requisition_items_table.php
2026_10_06_033456_create_purchase_orders_table.php
2026_10_06_033457_create_purchase_order_items_table.php
2026_10_06_041915_add_pr_item_id_to_purchase_order_items_table.php
2026_10_06_045214_create_goods_receipts_table.php
2026_10_06_045219_create_goods_receipt_items_table.php
2026_10_06_063527_create_supplier_prices_table.php
2026_10_06_072414_create_purchase_returns_table.php
2026_10_06_072417_create_purchase_return_items_table.php
2026_10_06_082228_create_notifications_table.php
2026_10_06_082401_create_supplier_evaluations_table.php
2026_10_06_090246_create_stock_opnames_table.php
2026_10_06_090247_create_stock_opname_items_table.php
2026_10_06_090248_add_adjustment_fields_to_stock_movements_table.php
2026_10_07_042352_add_overhead_rate_to_work_centers_table.php
2026_10_07_042353_create_boms_table.php
2026_10_07_042354_create_bom_items_table.php
2026_10_07_042355_create_routings_table.php
2026_10_07_042356_create_routing_steps_table.php
2026_10_07_063546_create_work_orders_table.php
2026_10_07_063547_create_work_order_materials_table.php
2026_10_07_063548_create_material_issues_table.php
2026_10_07_063549_create_material_issue_items_table.php
2026_10_07_063550_create_work_order_progress_table.php
2026_10_07_065528_create_cost_snapshots_table.php
2026_10_07_083134_add_costing_fields_to_materials_table.php
2026_10_07_083135_add_dimension_fields_to_bom_items_table.php
2026_10_08_080002_add_volume_type_to_materials_table.php
2026_10_09_014639_add_header_fields_to_bom_items_table.php
2026_10_09_061555_create_finished_goods_receipts_table.php
2026_10_09_061556_create_finished_goods_receipt_items_table.php
2026_10_14_160500_add_is_active_to_users_table.php
2026_10_15_043005_create_activity_log_table.php
2026_10_15_043013_create_settings_table.php
2026_10_15_152622_create_delivery_orders_table.php
2026_10_15_152623_create_delivery_order_items_table.php
2026_10_15_152624_create_delivery_order_status_logs_table.php
2026_10_22_043210_create_sales_invoices_table.php
2026_10_22_043211_create_sales_invoice_items_table.php
2026_10_26_061059_create_sales_returns_table.php
2026_10_26_061100_create_sales_return_items_table.php
```

## 6. Resume Model (app/Models)
```
app\Models\User.php
app\Models\Engineering\Bom.php
app\Models\Engineering\BomItem.php
app\Models\Engineering\Routing.php
app\Models\Engineering\RoutingStep.php
app\Models\Master\.gitkeep
app\Models\Master\Customer.php
app\Models\Master\Employee.php
app\Models\Master\Machine.php
app\Models\Master\Material.php
app\Models\Master\Product.php
app\Models\Master\Supplier.php
app\Models\Master\WorkCenter.php
app\Models\Production\CostSnapshot.php
app\Models\Production\FinishedGoodsReceipt.php
app\Models\Production\FinishedGoodsReceiptItem.php
app\Models\Production\MaterialIssue.php
app\Models\Production\MaterialIssueItem.php
app\Models\Production\WorkOrder.php
app\Models\Production\WorkOrderMaterial.php
app\Models\Production\WorkOrderProgress.php
app\Models\Purchasing\GoodsReceipt.php
app\Models\Purchasing\GoodsReceiptItem.php
app\Models\Purchasing\PurchaseOrder.php
app\Models\Purchasing\PurchaseOrderItem.php
app\Models\Purchasing\PurchaseRequisition.php
app\Models\Purchasing\PurchaseRequisitionItem.php
app\Models\Purchasing\PurchaseReturn.php
app\Models\Purchasing\PurchaseReturnItem.php
app\Models\Purchasing\SupplierEvaluation.php
app\Models\Purchasing\SupplierPrice.php
app\Models\Sales\DeliveryOrder.php
app\Models\Sales\DeliveryOrderItem.php
app\Models\Sales\DeliveryOrderStatusLog.php
app\Models\Sales\SalesInvoice.php
app\Models\Sales\SalesInvoiceItem.php
app\Models\Sales\SalesOrder.php
app\Models\Sales\SalesOrderItem.php
app\Models\Sales\SalesOrderStatusLog.php
app\Models\Sales\SalesReturn.php
app\Models\Sales\SalesReturnItem.php
app\Models\Warehouse\Inventory.php
app\Models\Warehouse\Location.php
app\Models\Warehouse\StockMovement.php
app\Models\Warehouse\StockOpname.php
app\Models\Warehouse\StockOpnameItem.php
app\Models\Warehouse\Warehouse.php
```

## 7. Resume Service & Controller
```
.gitkeep
BomService.php
CostingService.php
DeliveryOrderService.php
FinishedGoodsReceiptService.php
MaterialIssueService.php
RoutingService.php
SalesInvoiceService.php
SalesOrderStatusService.php
SalesReturnService.php
StockMovementService.php
SupplierEvaluationService.php
WorkOrderProgressService.php
WorkOrderService.php
app\Http\Controllers\BaseCrudController.php
app\Http\Controllers\Controller.php
app\Http\Controllers\DashboardController.php
app\Http\Controllers\ProfileController.php
app\Http\Controllers\Auth\AuthenticatedSessionController.php
app\Http\Controllers\Auth\ConfirmablePasswordController.php
app\Http\Controllers\Auth\EmailVerificationNotificationController.php
app\Http\Controllers\Auth\EmailVerificationPromptController.php
app\Http\Controllers\Auth\NewPasswordController.php
app\Http\Controllers\Auth\PasswordController.php
app\Http\Controllers\Auth\PasswordResetLinkController.php
app\Http\Controllers\Auth\RegisteredUserController.php
app\Http\Controllers\Auth\VerifyEmailController.php
app\Http\Controllers\Engineering\BomController.php
app\Http\Controllers\Engineering\BomImportController.php
app\Http\Controllers\Engineering\RoutingController.php
app\Http\Controllers\Master\.gitkeep
app\Http\Controllers\Master\CustomerController.php
app\Http\Controllers\Master\EmployeeController.php
app\Http\Controllers\Master\MachineController.php
app\Http\Controllers\Master\MaterialController.php
app\Http\Controllers\Master\MaterialImportController.php
app\Http\Controllers\Master\ProductController.php
app\Http\Controllers\Master\SupplierController.php
app\Http\Controllers\Master\WorkCenterController.php
app\Http\Controllers\Production\CostingController.php
app\Http\Controllers\Production\FinishedGoodsReceiptController.php
app\Http\Controllers\Production\MaterialIssueController.php
app\Http\Controllers\Production\WorkOrderController.php
app\Http\Controllers\Production\WorkOrderProgressController.php
app\Http\Controllers\Purchasing\GoodsReceiptController.php
app\Http\Controllers\Purchasing\PurchaseOrderController.php
app\Http\Controllers\Purchasing\PurchaseRequisitionController.php
app\Http\Controllers\Purchasing\PurchaseReturnController.php
app\Http\Controllers\Purchasing\PurchasingDashboardController.php
app\Http\Controllers\Purchasing\SupplierEvaluationController.php
app\Http\Controllers\Purchasing\SupplierPriceController.php
app\Http\Controllers\Sales\DeliveryOrderController.php
app\Http\Controllers\Sales\SalesInvoiceController.php
app\Http\Controllers\Sales\SalesOrderController.php
app\Http\Controllers\Sales\SalesReturnController.php
app\Http\Controllers\Settings\AuditLogController.php
app\Http\Controllers\Settings\RoleController.php
app\Http\Controllers\Settings\SystemSettingsController.php
app\Http\Controllers\Settings\UserController.php
app\Http\Controllers\Warehouse\InventoryController.php
app\Http\Controllers\Warehouse\LocationController.php
app\Http\Controllers\Warehouse\LowStockController.php
app\Http\Controllers\Warehouse\StockCardController.php
app\Http\Controllers\Warehouse\StockMovementController.php
app\Http\Controllers\Warehouse\StockOpnameController.php
app\Http\Controllers\Warehouse\WarehouseController.php
```

## 8. Resume Route (Ringkas)
```
 |  | 
```

## 9. Git Log (20 commit terakhir)
```
1b0c7cf feat: FASE 1.3 - Sales Return (Complete, Cancel, Auto Stock-In) & Finalize Sales Module
de48908 feat: FASE 1.2 - Sales Invoice (Generate, Print, Mark Paid) & Upgrade Template
95c7e7d feat: FASE 1.1 - Delivery Order (Create, Show, Ship, Deliver, Stock Movement)
f34dbf4 chore: hapus ExampleTest bawaan Laravel
99cf357 feat: FASE 4.2-4.5 - Role UI, System Settings, Audit Log, EnsureUserIsActive
a2b489c feat: FASE 4.1 - User CRUD dengan role assignment dan safety guards
b493a2b chore: hapus file .php
fc86245 feat: bulk view Master + bersihkan .gitignore
ec98981 feat: sorting di Customer, Supplier, Inventory, Work Order
46eb43a feat: sorting di Sales Order
f0659f9 feat: sorting di Product
7a40809 feat: sorting di Material
4972bb4 refactor: helper applySorting() di BaseCrudController
f121a56 feat: FG Receipt ΓÇö service, controller, route
0498ae4 feat: FG Receipt ΓÇö migration, model, relasi
14c6fd7 feat: import CSV BOM dengan header group & auto-match material
725f566 fix: rapikan routes/web.php + fix path quick-store
5cb7608 feat: BOM item form (level tombol, divisi tombol, satuan dropdown)
e61af7a feat: BomService support 5 costing method (per_unit, per_area, per_volume, per_length, per_weight)
a3189e8 feat: extend Material & BOM Items untuk 5 costing method
```


## 10. Blueprint Kode (Wajib Ditiru)
AI berikutnya WAJIB mengikuti gaya penulisan kode di bawah ini.

### A. Service Pattern (Contoh: SalesReturnService.php)
```php
<?php

namespace App\Services;

use App\Models\Sales\SalesReturn;
use App\Models\Sales\SalesReturnItem;
use App\Models\Sales\DeliveryOrder;
use App\Models\Warehouse\Inventory;
use App\Models\Warehouse\StockMovement;
use Illuminate\Support\Facades\DB;

class SalesReturnService
{
    /**
     * Generate nomor retur unik per bulan.
     * Format: SR-YYYYMM-XXXX
     */
    public static function generateNumber(): string
    {
        $prefix = 'SR-' . date('Ym') . '-';

        $last = SalesReturn::where('return_number', 'like', "{$prefix}%")
            ->orderByDesc('return_number')
            ->first();

        $lastNumber = $last ? (int) substr($last->return_number, -4) : 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Buat data Retur baru (status draft).
     */
    public static function create(array $data): SalesReturn
    {
        DB::beginTransaction();
        try {
            $do = DeliveryOrder::findOrFail($data['delivery_order_id']);

            // 1. Buat header Retur
            $return = SalesReturn::create([
                'return_number'     => self::generateNumber(),
                'delivery_order_id' => $do->id,
                'customer_id'       => $do->customer_id,
                'return_date'       => $data['return_date'],
                'status'            => 'draft',
                'reason'            => $data['reason'],
                'total_amount'      => 0, // Akan diupdate setelah items
                'notes'             => $data['notes'] ?? null,
                'created_by'        => auth()->id(),
            ]);

            // 2. Buat items
            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $qty = (float) $item['qty'];
                $price = (float) $item['unit_price'];
                $subtotal = $qty * $price;
                $totalAmount += $subtotal;

                SalesReturnItem::create([
                    'sales_return_id'        => $return->id,
                    'delivery_order_item_id' => $item['delivery_order_item_id'],
                    'product_id'             => $item['product_id'],
                    'location_id'            => $item['location_id'],
                    'qty'                    => $qty,
                    'unit_price'             => $price,
                    'subtotal'               => $subtotal,
                    'notes'                  => $item['notes'] ?? null,
                ]);
            }

            // 3. Update total di header
            $return->update(['total_amount' => $totalAmount]);

            DB::commit();

            return $return->fresh(['items', 'customer', 'deliveryOrder']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Proses Retur (status draft -> completed).
     * Menambah stok kembali ke gudang.
     */
    public static function complete(SalesReturn $return): SalesReturn
    {
        if ($return->status !== 'draft') {
            throw new \Exception("Hanya retur berstatus draft yang bisa diproses.");
        }

        DB::beginTransaction();
        try {
            $return->load('items.product', 'items.location');

            foreach ($return->items as $item) {
                // 1. Tambah stok di Inventory
                $inventory = Inventory::where('item_type', 'product')
                    ->where('item_id', $item->product_id)
                    ->where('location_id', $item->location_id)
                    ->first();

                // Jika stok di lokasi tersebut belum ada, buat baru
                if (!$inventory) {
                    $inventory = Inventory::create([
                        'item_type'   => 'product',
                        'item_id'     => $item->product_id,
                        'location_id' => $item->location_id,
                        'qty'         => 0,
                    ]);
                }

                $qtyBefore = (float) $inventory->qty;
                $qtyAfter  = $qtyBefore + (float) $item->qty;

                // Update stok
                $inventory->update([
                    'qty'              => $qtyAfter,
                    'last_movement_at' => now(),
                ]);

                // 2. Catat di Stock Movement (IN)
                StockMovement::create([
                    'movement_number'  => StockMovement::generateNumber(),
                    'item_type'        => 'product',
                    'item_id'          => $item->product_id,
                    'location_id'      => $item->location_id,
                    'type'             => 'in',
                    'qty'              => $item->qty,
                    'qty_before'       => $qtyBefore,
                    'qty_after'        => $qtyAfter,
                    'reference_type'   => SalesReturn::class,
                    'reference_id'     => $return->id,
                    'reference_number' => $return->return_number,
                    'date'             => $return->return_date,
                    'notes'            => "Retur dari customer " . ($return->customer->nama ?? '-'),
                    'created_by'       => auth()->id(),
                ]);
            }

            // 3. Update status retur
            $return->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            return $return->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Batalkan Retur.
     */
    public static function cancel(SalesReturn $return): SalesReturn
    {
        if ($return->status === 'cancelled') {
            throw new \Exception('Retur ini sudah dibatalkan.');
        }

        DB::beginTransaction();
        try {
            // Jika retur yang sudah completed dibatalkan, stok harus dikurangi kembali
            if ($return->status === 'completed') {
                $return->load('items');

                foreach ($return->items as $item) {
                    $inventory = Inventory::where('item_type', 'product')
                        ->where('item_id', $item->product_id)
                        ->where('location_id', $item->location_id)
                        ->first();

                    if ($inventory) {
                        $qtyBefore = (float) $inventory->qty;
                        $qtyAfter  = $qtyBefore - (float) $item->qty;

                        $inventory->update([
                            'qty'              => $qtyAfter,
                            'last_movement_at' => now(),
                        ]);

                        // Catat stock movement (OUT) karena retur dibatalkan
                        StockMovement::create([
                            'movement_number'  => StockMovement::generateNumber(),
                            'item_type'        => 'product',
                            'item_id'          => $item->product_id,
                            'location_id'      => $item->location_id,
                            'type'             => 'out',
                            'qty'              => $item->qty,
                            'qty_before'       => $qtyBefore,
                            'qty_after'        => $qtyAfter,
                            'reference_type'   => SalesReturn::class,
                            'reference_id'     => $return->id,
                            'reference_number' => $return->return_number,
                            'date'             => now()->toDateString(),
                            'notes'            => "Pembatalan retur (stok ditarik kembali)",
                            'created_by'       => auth()->id(),
                        ]);
                    }
                }
            }

            $return->update(['status' => 'cancelled']);

            DB::commit();

            return $return->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### B. Controller Pattern (Contoh: SalesInvoiceController.php)
```php
<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesInvoice;
use App\Services\SalesInvoiceService;
use Illuminate\Http\Request;

class SalesInvoiceController extends Controller
{
    /**
     * List semua Invoice.
     */
    public function index(Request $request)
    {
        $query = SalesInvoice::with(['customer', 'deliveryOrder'])
            ->withCount('items');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($c) => $c->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('deliveryOrder', fn($d) => $d->where('do_number', 'like', "%{$search}%"));
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'total'     => SalesInvoice::count(),
            'unpaid'    => SalesInvoice::where('status', 'unpaid')->count(),
            'paid'      => SalesInvoice::where('status', 'paid')->count(),
            'total_omzet'=> SalesInvoice::where('status', 'paid')->sum('total_amount'),
        ];

        return view('sales.invoices.index', compact('invoices', 'stats'));
    }

    /**
     * Generate Invoice dari Delivery Order.
     * Dipanggil dari halaman detail DO (via POST).
     */
    public function store(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'invoice_date' => ['nullable', 'date'],
            'due_date'     => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $invoice = SalesInvoiceService::createFromDeliveryOrder($deliveryOrder, $request->all());

            return redirect()
                ->route('sales.invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_number} berhasil dibuat.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat Invoice: ' . $e->getMessage());
        }
    }

    /**
     * Detail Invoice.
     */
    public function show(SalesInvoice $invoice)
    {
        $invoice->load([
            'items.product',
            'customer',
            'deliveryOrder',
            'creator',
        ]);

        return view('sales.invoices.show', compact('invoice'));
    }

    /**
     * Halaman Print / PDF.
     */
    public function print(SalesInvoice $invoice)
    {
        $invoice->load(['items.product', 'customer', 'deliveryOrder']);
        return view('sales.invoices.print', compact('invoice'));
    }

    /**
     * Tandai Lunas.
     */
    public function markPaid(SalesInvoice $invoice)
    {
        try {
            SalesInvoiceService::markAsPaid($invoice);
            return back()->with('success', 'Invoice berhasil ditandai lunas.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal update Invoice: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan Invoice (Opsional).
     */
    public function cancel(SalesInvoice $invoice)
    {
        if ($invoice->isPaid()) {
            return back()->with('error', 'Invoice yang sudah lunas tidak bisa dibatalkan.');
        }

        $invoice->update(['status' => 'cancelled']);
        return back()->with('success', 'Invoice dibatalkan.');
    }
}
```

### C. Model Pattern (Contoh: SalesInvoice.php)
```php
<?php

namespace App\Models\Sales;

use App\Models\Master\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'delivery_order_id',
        'customer_id',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'notes',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    // ========== RELASI ==========

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class, 'sales_invoice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ========== HELPERS ==========

    public function getOutstandingAmountAttribute(): float
    {
        return (float) ($this->total_amount - $this->paid_amount);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
```

### D. Migration Pattern (Contoh: create_sales_invoices_table.php)
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('status')->default('unpaid');
            // draft, unpaid, partial, paid, cancelled
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
```

