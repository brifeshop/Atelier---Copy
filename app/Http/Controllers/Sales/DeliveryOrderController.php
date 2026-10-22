<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreDeliveryOrderRequest;
use App\Http\Requests\Sales\UpdateDeliveryOrderRequest;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderItem;
use App\Models\Warehouse\Inventory;
use App\Models\Warehouse\Location;
use App\Models\Warehouse\Warehouse;
use App\Services\DeliveryOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryOrderController extends Controller
{
    /**
     * List DO dengan filter & sorting.
     */
    public function index(Request $request)
    {
        $query = DeliveryOrder::with(['customer', 'salesOrder', 'warehouse', 'creator'])
            ->withCount('items');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('do_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($c) => $c->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('salesOrder', fn($s) => $s->where('so_number', 'like', "%{$search}%"));
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter tanggal
        if ($request->filled('date_from')) {
            $query->where('delivery_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('delivery_date', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSorts = ['do_number', 'delivery_date', 'status', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $deliveryOrders = $query->paginate(20)->withQueryString();

// Data untuk filter
$customers = Customer::where('is_active', true)->orderBy('nama')->get();

// Statistik
$stats = [
    'total'     => DeliveryOrder::count(),
    'pending'   => DeliveryOrder::whereIn('status', ['draft', 'ready'])->count(),
    'shipped'   => DeliveryOrder::where('status', 'shipped')->count(),
    'delivered' => DeliveryOrder::where('status', 'delivered')->count(),
];

return view('sales.delivery-orders.index', compact('deliveryOrders', 'customers', 'stats'));
    }

    /**
     * Form create DO.
     */
    public function create(Request $request)
    {
        // Kalau ada sales_order_id di query, pre-load SO-nya
        $salesOrder = null;
        if ($request->filled('sales_order_id')) {
            $salesOrder = SalesOrder::with(['items.product', 'customer'])
                ->findOrFail($request->sales_order_id);
        }

        // SO yang bisa dibuat DO (belum closed/cancelled)
        $availableSalesOrders = SalesOrder::with('customer')
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->orderByDesc('order_date')
            ->limit(50)
            ->get();

        $warehouses = Warehouse::where('is_active', true)->orderBy('nama')->get();

        return view('sales.delivery-orders.create', compact(
            'salesOrder',
            'availableSalesOrders',
            'warehouses'
        ));
    }

    /**
     * Simpan DO baru.
     */
    public function store(StoreDeliveryOrderRequest $request)
    {
        try {
            $do = DeliveryOrderService::create($request->validated());

            return redirect()
                ->route('sales.delivery-orders.show', $do)
                ->with('success', "Delivery Order {$do->do_number} berhasil dibuat.");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat DO: ' . $e->getMessage());
        }
    }

    /**
     * Detail DO.
     */
    public function show(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load([
            'items.product',
            'items.salesOrderItem',
            'items.location',
            'customer',
            'salesOrder.items',
            'warehouse',
            'creator',
            'shipper',
            'deliverer',
            'statusLogs.changedBy',
        ]);

        return view('sales.delivery-orders.show', compact('deliveryOrder'));
    }

    /**
     * Form edit DO.
     */
    public function edit(DeliveryOrder $deliveryOrder)
    {
        if (!$deliveryOrder->isEditable()) {
            return redirect()
                ->route('sales.delivery-orders.show', $deliveryOrder)
                ->with('error', 'DO dengan status ini tidak dapat diedit.');
        }

        $deliveryOrder->load(['items.product', 'items.location', 'customer', 'salesOrder']);

        $warehouses = Warehouse::where('is_active', true)->orderBy('nama')->get();

        return view('sales.delivery-orders.edit', compact('deliveryOrder', 'warehouses'));
    }

    /**
     * Update DO.
     */
    public function update(UpdateDeliveryOrderRequest $request, DeliveryOrder $deliveryOrder)
    {
        try {
            $do = DeliveryOrderService::update($deliveryOrder, $request->validated());

            return redirect()
                ->route('sales.delivery-orders.show', $do)
                ->with('success', "Delivery Order {$do->do_number} berhasil diperbarui.");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal update DO: ' . $e->getMessage());
        }
    }

    /**
     * Hapus DO (hanya draft).
     */
    public function destroy(DeliveryOrder $deliveryOrder)
    {
        try {
            $doNumber = $deliveryOrder->do_number;
            DeliveryOrderService::delete($deliveryOrder);

            return redirect()
                ->route('sales.delivery-orders.index')
                ->with('success', "Delivery Order {$doNumber} berhasil dihapus.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal hapus DO: ' . $e->getMessage());
        }
    }

    /**
     * Mark ready (draft → ready).
     */
    public function markReady(DeliveryOrder $deliveryOrder)
    {
        try {
            DeliveryOrderService::markReady($deliveryOrder);

            return back()->with('success', 'DO siap dikirim.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mark ready: ' . $e->getMessage());
        }
    }

    /**
     * Ship DO — kurangi stok.
     */
    public function ship(DeliveryOrder $deliveryOrder)
    {
        try {
            DeliveryOrderService::ship($deliveryOrder);

            return back()->with('success', 'DO berhasil dikirim. Stok produk sudah dikurangi.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal kirim DO: ' . $e->getMessage());
        }
    }

    /**
     * Deliver DO — customer terima.
     */
    public function deliver(DeliveryOrder $deliveryOrder)
    {
        try {
            DeliveryOrderService::deliver($deliveryOrder);

            return back()->with('success', 'DO berhasil diterima customer.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal deliver DO: ' . $e->getMessage());
        }
    }

    /**
     * Cancel DO.
     */
    public function cancel(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DeliveryOrderService::cancel($deliveryOrder, $request->reason);

            return back()->with('success', 'DO berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal cancel DO: ' . $e->getMessage());
        }
    }

    /**
     * API: Ambil detail SO untuk form create (via AJAX).
     */
    public function getSalesOrderDetails(SalesOrder $salesOrder)
    {
        $salesOrder->load(['items.product', 'items.deliveryOrderItems', 'customer']);

        $items = $salesOrder->items->map(function ($item) {
            // Hitung qty yang sudah dijadwalkan di DO lain (bukan cancelled)
            $alreadyDelivered = $item->deliveryOrderItems()
                ->whereHas('deliveryOrder', function ($q) {
                    $q->whereNotIn('status', ['cancelled']);
                })
                ->sum('qty_delivered');

            return [
                'id'              => $item->id,
                'product_id'      => $item->product_id,
                'product_nama'    => $item->product->nama ?? '-',
                'product_kode'    => $item->product->kode ?? '-',
                'qty'             => (float) $item->qty,
                'qty_remaining'   => (float) ($item->qty - $alreadyDelivered),
                'unit_price'      => (float) $item->unit_price,
            ];
        });

        return response()->json([
            'sales_order' => [
                'id'          => $salesOrder->id,
                'so_number'   => $salesOrder->so_number,
                'customer_id' => $salesOrder->customer_id,
                'customer'    => $salesOrder->customer->nama ?? '-',
                'address'     => $salesOrder->customer->address ?? '-',
                'status'      => $salesOrder->status,
            ],
            'items' => $items,
        ]);
    }

        /**
     * API: Ambil lokasi berdasarkan warehouse.
     */
    public function getLocations(Warehouse $warehouse)
    {
        $locations = Location::where('warehouse_id', $warehouse->id)
            ->orderBy('kode')
            ->get(['id', 'kode', 'nama']);

        // Format ulang agar ID dijamin berupa string untuk Alpine.js
        $formatted = $locations->map(function($loc) {
            return [
                'id' => (string) $loc->id,
                'kode' => $loc->kode,
                'nama' => $loc->nama,
            ];
        });

        return response()->json($formatted);
    }

    /**
     * API: Cek stok produk di lokasi tertentu.
     */
    public function checkStock(Request $request)
    {
        $request->validate([
            'product_id'  => ['required', 'integer', 'exists:products,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);

        $inventory = Inventory::where('item_type', 'product')
            ->where('item_id', $request->product_id)
            ->where('location_id', $request->location_id)
            ->first();

        return response()->json([
            'available' => $inventory ? (float) $inventory->qty : 0,
        ]);
    }
}