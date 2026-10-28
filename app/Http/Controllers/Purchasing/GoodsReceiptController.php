<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Purchasing\GoodsReceipt;
use App\Models\Purchasing\GoodsReceiptItem;
use App\Models\Purchasing\PurchaseOrder;
use App\Models\Warehouse\Location;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = GoodsReceipt::with(['supplier', 'purchaseOrder', 'receivedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gr_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($q2) use ($search) {
                      $q2->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        $items = $query->latest('date')->latest('id')->paginate(15);

        $stats = [
            'total' => GoodsReceipt::count(),
            'draft' => GoodsReceipt::where('status', 'draft')->count(),
            'received' => GoodsReceipt::where('status', 'received')->count(),
        ];

        return view('purchasing.goods-receipts.index', compact('items', 'stats'));
    }

    public function create(Request $request)
    {
        $po = null;
        $locations = Location::active()->with('warehouse')->orderBy('kode')->get();

        if ($request->filled('from_po')) {
            $po = PurchaseOrder::with(['items.material', 'supplier', 'goodsReceipts.items'])
                ->find($request->from_po);
        }

        return view('purchasing.goods-receipts.create', [
            'generatedNumber' => GoodsReceipt::generateNumber(),
            'po' => $po,
            'locations' => $locations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gr_number' => 'required|string|max:50|unique:goods_receipts,gr_number',
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.location_id' => 'required|exists:locations,id',
            'items.*.qty_received' => 'required|numeric|min:0',
            'items.*.qty_rejected' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $po = PurchaseOrder::findOrFail($validated['purchase_order_id']);

            $gr = GoodsReceipt::create([
                'gr_number' => $validated['gr_number'],
                'purchase_order_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'date' => $validated['date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'received_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                $poItem = $po->items()->find($itemData['purchase_order_item_id']);
                if (!$poItem) continue;

                if ($itemData['qty_received'] <= 0) continue;

                GoodsReceiptItem::create([
                    'goods_receipt_id' => $gr->id,
                    'purchase_order_item_id' => $poItem->id,
                    'material_id' => $poItem->material_id,
                    'location_id' => $itemData['location_id'],
                    'qty_ordered' => $poItem->qty - $poItem->received_qty,
                    'qty_received' => $itemData['qty_received'],
                    'qty_rejected' => $itemData['qty_rejected'] ?? 0,
                    'unit_price' => $poItem->unit_price,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('purchasing.goods-receipts.show', $gr)
                ->with('success', "GR {$gr->gr_number} berhasil dibuat. Klik 'Konfirmasi Penerimaan' untuk update stok.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan GR: ' . $e->getMessage());
        }
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load(['supplier', 'purchaseOrder', 'items.material', 'items.location', 'receivedBy']);
        return view('purchasing.goods-receipts.show', ['item' => $goodsReceipt]);
    }

    public function edit(GoodsReceipt $goodsReceipt)
    {
        if (!$goodsReceipt->canEdit()) {
            return redirect()
                ->route('purchasing.goods-receipts.show', $goodsReceipt)
                ->with('error', 'GR tidak bisa diedit.');
        }

        $goodsReceipt->load('items');
        $locations = Location::active()->with('warehouse')->orderBy('kode')->get();

        return view('purchasing.goods-receipts.edit', [
            'item' => $goodsReceipt,
            'locations' => $locations,
        ]);
    }

    public function update(Request $request, GoodsReceipt $goodsReceipt)
    {
        if (!$goodsReceipt->canEdit()) {
            return redirect()
                ->route('purchasing.goods-receipts.show', $goodsReceipt)
                ->with('error', 'GR tidak bisa diedit.');
        }

        $validated = $request->validate([
            'gr_number' => 'required|string|max:50|unique:goods_receipts,gr_number,' . $goodsReceipt->id,
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:goods_receipt_items,id',
            'items.*.location_id' => 'required|exists:locations,id',
            'items.*.qty_received' => 'required|numeric|min:0',
            'items.*.qty_rejected' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $goodsReceipt->update([
                'gr_number' => $validated['gr_number'],
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $grItem = $goodsReceipt->items()->find($itemData['id']);
                if (!$grItem) continue;

                $grItem->update([
                    'location_id' => $itemData['location_id'],
                    'qty_received' => $itemData['qty_received'],
                    'qty_rejected' => $itemData['qty_rejected'] ?? 0,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('purchasing.goods-receipts.show', $goodsReceipt)
                ->with('success', 'GR berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal update GR: ' . $e->getMessage());
        }
    }

    /**
     * KONFIRMASI PENERIMAAN
     * 
     * Ini yang paling penting:
     * - Update received_qty di PO Items
     * - Panggil StockMovementService::recordIn() untuk setiap item
     * - Update status PO (partial / received)
     */
            public function receive(GoodsReceipt $goodsReceipt)
    {
        if (!$goodsReceipt->canReceive()) {
            return back()->with('error', 'GR tidak bisa dikonfirmasi.');
        }

        DB::beginTransaction();
        try {
            $goodsReceipt->load('items');

            // Cari lokasi Karantina QC (ID 38)
            $locKarantina = \App\Models\Warehouse\Location::where('kode', 'QC-KARANTINA')->first();
            if (!$locKarantina) {
                throw new \Exception('Lokasi Karantina QC belum disetup. Jalankan seeder WarehouseQcSeeder.');
            }

            foreach ($goodsReceipt->items as $grItem) {
                if ($grItem->qty_received <= 0) continue;

                // 1. Panggil Stock Movement (IN) ke Karantina (bukan ke lokasi asli)
                StockMovementService::recordIn(
                    'material',
                    $grItem->material_id,
                    $locKarantina->id, // <-- OVERRIDE LOKASI
                    (float) $grItem->qty_received,
                    [
                        'unit_price' => (float) $grItem->unit_price,
                        'reference_type' => GoodsReceipt::class,
                        'reference_id' => $goodsReceipt->id,
                        'reference_number' => $goodsReceipt->gr_number,
                        'date' => $goodsReceipt->date->toDateString(),
                        'notes' => "Penerimaan (Karantina IQC) dari GR {$goodsReceipt->gr_number}",
                    ]
                );

                // 2. Update received_qty di PO Item
                if ($grItem->purchaseOrderItem) {
                    $grItem->purchaseOrderItem->increment('received_qty', $grItem->qty_received);
                }
            }

            // 3. Update status GR
            $goodsReceipt->update([
                'status' => 'received',
                'received_at' => now(),
            ]);

            // 4. Auto-create AP
            try {
                \App\Services\Finance\ApService::createFromGoodsReceipt($goodsReceipt);
            } catch (\Exception $e) {
                \Log::error('Gagal auto-create AP untuk GR ' . $goodsReceipt->gr_number . ': ' . $e->getMessage());
            }

            // 5. Auto-create QC Inspection (Pending)
            try {
                \App\Services\QcInspectionService::createFromGoodsReceipt($goodsReceipt);
            } catch (\Exception $e) {
                \Log::error('Gagal auto-create QC untuk GR ' . $goodsReceipt->gr_number . ': ' . $e->getMessage());
            }

            // 6. Notifikasi Reject (jika ada)
            $rejects = $goodsReceipt->items->filter(fn($i) => $i->qty_rejected > 0);
            if ($rejects->count() > 0) {
                $purchasingUsers = \App\Models\User::role('Purchasing')->get();
                foreach ($purchasingUsers as $user) {
                    $user->notify(new \App\Notifications\GoodsRejectNotification(
                        $goodsReceipt,
                        $rejects->map(fn($i) => [
                            'material' => $i->material->nama ?? '-',
                            'qty_rejected' => (float) $i->qty_rejected,
                        ])->toArray()
                    ));
                }
            }

            // 7. Update status PO
            $po = $goodsReceipt->purchaseOrder;
            if ($po) {
                $po->refresh();
                $allReceived = $po->items->every(fn($i) => $i->received_qty >= $i->qty);
                if ($allReceived) {
                    $po->update(['status' => 'received', 'received_at' => now()]);
                } else {
                    $po->update(['status' => 'partial']);
                }
            }

            DB::commit();

            return redirect()
                ->route('purchasing.goods-receipts.show', $goodsReceipt)
                ->with('success', "GR {$goodsReceipt->gr_number} diterima. Barang masuk ke Karantina IQC. Silakan cek menu QC Incoming.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal konfirmasi GR: ' . $e->getMessage());
        }
    }

    public function destroy(GoodsReceipt $goodsReceipt)
    {
        if ($goodsReceipt->status !== 'draft') {
            return back()->with('error', 'Hanya GR draft yang bisa dihapus.');
        }

        $grNumber = $goodsReceipt->gr_number;
        $goodsReceipt->delete();

        return redirect()
            ->route('purchasing.goods-receipts.index')
            ->with('success', "GR {$grNumber} berhasil dihapus.");
    }
}