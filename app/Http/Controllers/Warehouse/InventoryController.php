<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Inventory;
use App\Models\Warehouse\Location;
use App\Models\Warehouse\Warehouse;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Traits\Sortable;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use Sortable;

    protected array $sortable = ['qty', 'min_stock', 'max_stock', 'last_movement_at', 'created_at'];
    protected string $orderColumn = 'last_movement_at';

    /**
     * List semua inventory
     */
    public function index(Request $request)
        {
            $query = Inventory::with(['location.warehouse', 'item']);

            // Filter by warehouse
            if ($request->filled('warehouse_id')) {
                $query->whereHas('location', function ($q) use ($request) {
                    $q->where('warehouse_id', $request->warehouse_id);
                });
            }

            // Filter by type
            if ($request->filled('item_type')) {
                $query->where('item_type', $request->item_type);
            }

            // Filter low stock
            if ($request->filled('low_stock')) {
                $query->lowStock();
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('item', function ($q2) use ($search) {
                        $q2->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%");
                    });
                });
            }

            $this->applySorting($query, $request);
                $query->orderBy('id', 'desc');

                $items = $query->paginate(15)->withQueryString();

            // Hitung total value dari SEMUA inventory (bukan cuma yang di halaman ini)
            $totalValue = 0;
            Inventory::with('item')->where('qty', '>', 0)->chunk(100, function ($inventories) use (&$totalValue) {
                foreach ($inventories as $inv) {
                    if (!$inv->item) continue;
                    
                    $price = 0;
                    if ($inv->item_type === 'material') {
                        $price = (float) ($inv->item->price ?? 0);
                    } elseif ($inv->item_type === 'product') {
                        $price = (float) ($inv->item->selling_price ?? 0);
                    }
                    
                    $totalValue += (float) $inv->qty * $price;
                }
            });

            // Stats
            $stats = [
                'total_items' => Inventory::where('qty', '>', 0)->count(),
                'total_locations' => Location::active()->count(),
                'low_stock_count' => Inventory::lowStock()->count(),
                'total_value' => $totalValue,
            ];

            return view('warehouse.inventories.index', [
                'items' => $items,
                'stats' => $stats,
                'warehouses' => Warehouse::active()->orderBy('nama')->get(),
            ]);
        }

    /**
     * Show detail inventory
     */
    public function show(Inventory $inventory)
    {
        $inventory->load(['item', 'location.warehouse']);

        $movements = \App\Models\Warehouse\StockMovement::where('item_type', $inventory->item_type)
            ->where('item_id', $inventory->item_id)
            ->where('location_id', $inventory->location_id)
            ->with(['createdBy']) // <--- Ubah jadi createdBy
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('warehouse.inventories.show', compact('inventory', 'movements'));
    }

    /**
     * Update min/max stock
     */
    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
        ]);

        $inventory->update($validated);

        return back()->with('success', 'Setting stok berhasil diperbarui.');
    }
}