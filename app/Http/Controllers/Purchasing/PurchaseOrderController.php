<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Purchasing\PurchaseOrder;
use App\Models\Purchasing\PurchaseOrderItem;
use App\Models\Purchasing\PurchaseRequisition;
use App\Models\Master\Supplier;
use App\Models\Master\Material;
use App\Traits\Sortable;   // ← TAMBAH
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    use Sortable;   // ← TAMBAH

    protected array $sortable = [
        'po_number',
        'date',
        'delivery_date',
        'total_amount',
        'status',
        'created_at',
    ];

    protected string $orderColumn = 'date';   // ← Default sort column

    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'createdBy']);

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                ->orWhereHas('supplier', function ($q2) use ($search) {
                    $q2->where('nama', 'like', "%{$search}%");
                });
            });
        }

        // Sorting — via trait
        $this->applySorting($query, $request);

        // Secondary sort by id untuk konsistensi
        $query->orderBy('id', 'desc');

        $items = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => PurchaseOrder::count(),
            'draft' => PurchaseOrder::where('status', 'draft')->count(),
            'sent' => PurchaseOrder::where('status', 'sent')->count(),
            'received' => PurchaseOrder::whereIn('status', ['received', 'closed'])->count(),
        ];

        return view('purchasing.purchase-orders.index', compact('items', 'stats'));
    }

    public function create(Request $request)
    {
        $pr = null;
        $prItems = collect();
        $selectedPrItemIds = [];

        if ($request->filled('from_pr')) {
            $pr = PurchaseRequisition::with(['items.material', 'items.purchaseOrderItems'])->find($request->from_pr);
            
            if ($pr) {
                // Filter item PR yang belum di-PO semua
                $prItems = $pr->items->filter(fn($i) => $i->is_pending);
                
                // Kalau ada request item spesifik
                if ($request->filled('item_ids')) {
                    $selectedIds = explode(',', $request->item_ids);
                    $prItems = $prItems->filter(fn($i) => in_array($i->id, $selectedIds));
                }
                
                $selectedPrItemIds = $prItems->pluck('id')->toArray();
            }
        }

        return view('purchasing.purchase-orders.create', [
            'generatedNumber' => PurchaseOrder::generateNumber(),
            'suppliers' => Supplier::active()->orderBy('nama')->get(),
            'materials' => Material::active()->orderBy('nama')->get(),
            'pr' => $pr,
            'prItems' => $prItems,
            'selectedPrItemIds' => $selectedPrItemIds,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_number' => 'required|string|max:50|unique:purchase_orders,po_number',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_requisition_id' => 'nullable|exists:purchase_requisitions,id',
            'date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:date',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.purchase_requisition_item_id' => 'nullable|exists:purchase_requisition_items,id',  // ← TAMBAHKAN
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $po = PurchaseOrder::create([
                'po_number' => $validated['po_number'],
                'supplier_id' => $validated['supplier_id'],
                'purchase_requisition_id' => $validated['purchase_requisition_id'] ?? null,
                'date' => $validated['date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'status' => 'draft',
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'purchase_requisition_item_id' => $itemData['purchase_requisition_item_id'] ?? null,  // ← TAMBAHKAN
                    'material_id' => $itemData['material_id'],
                    'qty' => $itemData['qty'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_percent' => $itemData['discount_percent'] ?? 0,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            $po->load('items');
            $po->recalculateTotals();

            DB::commit();

            return redirect()
                ->route('purchasing.purchase-orders.show', $po)
                ->with('success', "PO {$po->po_number} berhasil dibuat.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan PO: ' . $e->getMessage());
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'supplier',
            'purchaseRequisition',
            'items.material',
            'items.goodsReceiptItems',   // ← TAMBAHKAN
            'createdBy',
            'goodsReceipts',
        ]);
        return view('purchasing.purchase-orders.show', ['item' => $purchaseOrder]);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        if (!$purchaseOrder->canEdit()) {
            return redirect()
                ->route('purchasing.purchase-orders.show', $purchaseOrder)
                ->with('error', 'PO tidak bisa diedit.');
        }

        $purchaseOrder->load('items');

        return view('purchasing.purchase-orders.edit', [
            'item' => $purchaseOrder,
            'suppliers' => Supplier::active()->orderBy('nama')->get(),
            'materials' => Material::active()->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (!$purchaseOrder->canEdit()) {
            return redirect()
                ->route('purchasing.purchase-orders.show', $purchaseOrder)
                ->with('error', 'PO tidak bisa diedit.');
        }

        $validated = $request->validate([
            'po_number' => 'required|string|max:50|unique:purchase_orders,po_number,' . $purchaseOrder->id,
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:date',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $purchaseOrder->update([
                'po_number' => $validated['po_number'],
                'supplier_id' => $validated['supplier_id'],
                'date' => $validated['date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Hapus item lama, buat baru
            $purchaseOrder->items()->delete();

            foreach ($validated['items'] as $itemData) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'material_id' => $itemData['material_id'],
                    'qty' => $itemData['qty'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_percent' => $itemData['discount_percent'] ?? 0,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            $purchaseOrder->load('items');
            $purchaseOrder->recalculateTotals();

            DB::commit();

            return redirect()
                ->route('purchasing.purchase-orders.show', $purchaseOrder)
                ->with('success', 'PO berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal update PO: ' . $e->getMessage());
        }
    }

    public function send(PurchaseOrder $purchaseOrder)
    {
        if (!$purchaseOrder->canSend()) {
            return back()->with('error', 'PO tidak bisa dikirim.');
        }

        $purchaseOrder->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return back()->with('success', "PO {$purchaseOrder->po_number} berhasil dikirim ke supplier.");
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if (!$purchaseOrder->canCancel()) {
            return back()->with('error', 'PO tidak bisa dibatalkan.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return back()->with('success', "PO {$purchaseOrder->po_number} dibatalkan.");
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Hanya PO draft yang bisa dihapus.');
        }

        $poNumber = $purchaseOrder->po_number;
        $purchaseOrder->delete();

        return redirect()
            ->route('purchasing.purchase-orders.index')
            ->with('success', "PO {$poNumber} berhasil dihapus.");
    }
}