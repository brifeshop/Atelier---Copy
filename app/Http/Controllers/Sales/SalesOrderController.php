<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Master\Customer;
use App\Models\Master\Employee;
use App\Models\Master\Product;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderItem;
use App\Traits\Sortable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SalesOrderStatusService;

class SalesOrderController extends Controller
{
    use Sortable;

    protected array $sortable = [
        'so_number',
        'order_date',
        'delivery_date',
        'total_amount',
        'status',
        'created_at',
    ];

    protected string $orderColumn = 'order_date';

    /**
     * List SO
     */
    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'salesPerson']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('so_number', 'like', "%{$search}%")
                ->orWhereHas('customer', function ($q2) use ($search) {
                    $q2->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%");
                });
            });
        }

        // Sorting — via trait
        $this->applySorting($query, $request);
        $query->orderBy('id', 'desc');

        $items = $query->paginate(15)->withQueryString();

        // Stats
        $stats = [
            'total' => SalesOrder::count(),
            'draft' => SalesOrder::where('status', 'draft')->count(),
            'in_production' => SalesOrder::where('status', 'in_production')->count(),
            'delivered' => SalesOrder::where('status', 'delivered')->count(),
        ];

        return view('sales.sales-orders.index', compact('items', 'stats'));
    }

    /**
     * Form create
     */
    public function create()
    {
        $customers = Customer::active()->orderBy('nama')->get();
        $salesPersons = Employee::active()->orderBy('nama')->get();
        $products = Product::active()->orderBy('nama')->get();

        return view('sales.sales-orders.create', [
            'generatedSoNumber' => SalesOrder::generateSoNumber(),
            'customers' => $customers,
            'salesPersons' => $salesPersons,
            'products' => $products,
        ]);
    }

    /**
     * Store SO
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'so_number' => 'required|string|max:50|unique:sales_orders,so_number',
            'customer_id' => 'required|exists:customers,id',
            'sales_person_id' => 'nullable|exists:employees,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
        ], [
            'items.required' => 'Minimal harus ada 1 item produk.',
            'items.min' => 'Minimal harus ada 1 item produk.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.qty.required' => 'Qty wajib diisi.',
            'items.*.qty.min' => 'Qty minimal 0.01.',
            'items.*.unit_price.required' => 'Harga jual wajib diisi.',
        ]);

        DB::beginTransaction();
        try {
            $so = SalesOrder::create([
                'so_number' => $validated['so_number'],
                'customer_id' => $validated['customer_id'],
                'sales_person_id' => $validated['sales_person_id'] ?? null,
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'status' => 'draft',
                'commission_rate' => $validated['commission_rate'] ?? 2.00,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => $itemData['product_id'],
                    'qty' => $itemData['qty'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_percent' => $itemData['discount_percent'] ?? 0,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            $so->load('items');
            $so->recalculateTotals();

            DB::commit();

            return redirect()
                ->route('sales.sales-orders.show', $so)
                ->with('success', "Sales Order {$so->so_number} berhasil dibuat.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan SO: ' . $e->getMessage());
        }
    }

    /**
     * Show SO
     */
    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'salesPerson', 'items.product', 'createdBy']);
        return view('sales.sales-orders.show', ['item' => $salesOrder]);
    }

    /**
     * Form edit
     */
    public function edit(SalesOrder $salesOrder)
    {
        if (!$salesOrder->canEdit()) {
            return redirect()
                ->route('sales.sales-orders.show', $salesOrder)
                ->with('error', 'SO tidak bisa diedit karena sudah dalam tahap produksi atau selesai.');
        }

        $salesOrder->load('items');
        $customers = Customer::active()->orderBy('nama')->get();
        $salesPersons = Employee::active()->orderBy('nama')->get();
        $products = Product::active()->orderBy('nama')->get();

        return view('sales.sales-orders.edit', [
            'item' => $salesOrder,
            'customers' => $customers,
            'salesPersons' => $salesPersons,
            'products' => $products,
        ]);
    }

    /**
     * Update SO
     */
    public function update(Request $request, SalesOrder $salesOrder)
    {
        if (!$salesOrder->canEdit()) {
            return redirect()
                ->route('sales.sales-orders.show', $salesOrder)
                ->with('error', 'SO tidak bisa diedit.');
        }

        $validated = $request->validate([
            'so_number' => 'required|string|max:50|unique:sales_orders,so_number,' . $salesOrder->id,
            'customer_id' => 'required|exists:customers,id',
            'sales_person_id' => 'nullable|exists:employees,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $salesOrder->update([
                'so_number' => $validated['so_number'],
                'customer_id' => $validated['customer_id'],
                'sales_person_id' => $validated['sales_person_id'] ?? null,
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'commission_rate' => $validated['commission_rate'] ?? 2.00,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Hapus item lama, buat baru
            $salesOrder->items()->delete();

            foreach ($validated['items'] as $itemData) {
                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $itemData['product_id'],
                    'qty' => $itemData['qty'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_percent' => $itemData['discount_percent'] ?? 0,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            $salesOrder->load('items');
            $salesOrder->recalculateTotals();

            DB::commit();

            return redirect()
                ->route('sales.sales-orders.show', $salesOrder)
                ->with('success', 'Sales Order berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal update SO: ' . $e->getMessage());
        }
    }

    /**
     * Update status
     */
    public function updateStatus(Request $request, SalesOrder $salesOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,in_production,delivered,paid,closed,cancelled',
        ]);

        $newStatus = $validated['status'];

        match ($newStatus) {
            'confirmed' => SalesOrderStatusService::confirm($salesOrder),
            'cancelled' => SalesOrderStatusService::cancel($salesOrder),
            'closed' => SalesOrderStatusService::close($salesOrder),
            'in_production', 'delivered', 'paid' => SalesOrderStatusService::changeStatus(
                $salesOrder,
                $newStatus,
                "Manual: Status diubah ke {$newStatus}"
            ),
        };

        return back()->with('success', "Status SO berhasil diubah ke " . ucfirst($newStatus) . ".");
    }

    /**
     * Delete SO
     */
    public function destroy(SalesOrder $salesOrder)
    {
        // Hanya bisa hapus kalau draft
        if ($salesOrder->status !== 'draft') {
            return back()->with('error', 'Hanya SO dengan status Draft yang bisa dihapus.');
        }

        $soNumber = $salesOrder->so_number;
        $salesOrder->delete();

        return redirect()
            ->route('sales.sales-orders.index')
            ->with('success', "Sales Order {$soNumber} berhasil dihapus.");
    }
}