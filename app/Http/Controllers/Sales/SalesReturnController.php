<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSalesReturnRequest;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesReturn;
use App\Models\Sales\SalesReturnItem;
use App\Models\Warehouse\Location;
use App\Services\SalesReturnService;
use Illuminate\Http\Request;

class SalesReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesReturn::with(['customer', 'deliveryOrder', 'creator'])
            ->withCount('items');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($c) => $c->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $returns = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'total' => SalesReturn::count(),
            'draft' => SalesReturn::where('status', 'draft')->count(),
            'completed' => SalesReturn::where('status', 'completed')->count(),
        ];

        return view('sales.returns.index', compact('returns', 'stats'));
    }

        public function create(Request $request)
    {
        $availableDeliveryOrders = DeliveryOrder::with('customer')
            ->whereIn('status', ['shipped', 'delivered'])
            ->orderByDesc('delivery_date')
            ->get(); // Hapus limit agar semua DO muncul

        // Ambil lokasi
        $locations = Location::with('warehouse')->orderBy('kode')->get();

        // Jika ada delivery_order_id dari URL, kita teruskan ke view untuk auto-select
        $selectedDoId = $request->query('delivery_order_id');

        return view('sales.returns.create', compact('availableDeliveryOrders', 'locations', 'selectedDoId'));
    }

    public function store(StoreSalesReturnRequest $request)
    {
        try {
            $return = SalesReturnService::create($request->validated());

            return redirect()
                ->route('sales.returns.show', $return)
                ->with('success', "Retur {$return->return_number} berhasil dibuat.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal membuat retur: ' . $e->getMessage());
        }
    }

    public function show(SalesReturn $return)
    {
        $return->load(['items.product', 'items.location', 'customer', 'deliveryOrder', 'creator']);
        return view('sales.returns.show', compact('return'));
    }

    public function complete(SalesReturn $return)
    {
        try {
            SalesReturnService::complete($return);
            return back()->with('success', 'Retur berhasil diproses. Stok telah ditambahkan kembali.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses retur: ' . $e->getMessage());
        }
    }

    public function cancel(SalesReturn $return)
    {
        try {
            SalesReturnService::cancel($return);
            return back()->with('success', 'Retur berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan retur: ' . $e->getMessage());
        }
    }

    /**
     * API: Ambil detail item dari DO yang bisa diretur.
     */
    public function getDeliveryOrderDetails(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['items.product', 'items.salesOrderItem', 'customer']);

        $items = $deliveryOrder->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_nama' => $item->product->nama ?? '-',
                'product_kode' => $item->product->kode ?? '-',
                'qty_shipped' => (float) $item->qty_shipped,
                'unit_price' => (float) ($item->salesOrderItem->unit_price ?? 0),
            ];
        });

        return response()->json([
            'delivery_order' => [
                'id' => $deliveryOrder->id,
                'do_number' => $deliveryOrder->do_number,
                'customer' => $deliveryOrder->customer->nama ?? '-',
            ],
            'items' => $items,
        ]);
    }
}