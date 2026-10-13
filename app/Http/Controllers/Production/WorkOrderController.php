<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\WorkOrder;
use App\Models\Engineering\Bom;
use App\Models\Engineering\Routing;
use App\Models\Master\Product;
use App\Models\Sales\SalesOrder;
use App\Services\WorkOrderService;
use App\Traits\Sortable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\CostingService;
use App\Models\Production\CostSnapshot;

class WorkOrderController extends Controller
{
    use Sortable;

    protected array $sortable = [
        'wo_number',
        'planned_qty',
        'actual_qty',
        'start_date',
        'end_date',
        'status',
        'priority',
        'created_at',
    ];

    protected string $orderColumn = 'start_date';

    public function index(Request $request)
    {
        $query = WorkOrder::with(['product', 'creator']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('wo_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($q2) use ($search) {
                      $q2->where('nama', 'like', "%{$search}%")
                         ->orWhere('kode', 'like', "%{$search}%");
                  });
            });
        }

        $this->applySorting($query, $request);
        $query->orderBy('id', 'desc');

        $items = $query->paginate(20)->withQueryString();

        $stats = [
            'total'       => WorkOrder::count(),
            'draft'       => WorkOrder::where('status', 'draft')->count(),
            'released'    => WorkOrder::where('status', 'released')->count(),
            'in_progress' => WorkOrder::where('status', 'in_progress')->count(),
            'completed'   => WorkOrder::where('status', 'completed')->count(),
        ];

        return view('production.work-orders.index', [
            'items'    => $items,
            'stats'    => $stats,
            'products' => Product::active()->orderBy('nama')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $salesOrder = null;
        if ($request->filled('sales_order_id')) {
            $salesOrder = SalesOrder::with('items.product')->find($request->sales_order_id);
        }

        return view('production.work-orders.create', [
            'generatedNumber' => WorkOrder::generateNumber(),
            'products'        => Product::active()->orderBy('nama')->get(),
            'boms'            => Bom::where('status', 'active')->with('product')->orderByDesc('effective_date')->get(),
            'routings'        => Routing::where('status', 'active')->with('product')->orderByDesc('effective_date')->get(),
            'salesOrder'      => $salesOrder,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'wo_number'         => 'required|string|max:50|unique:work_orders,wo_number',
            'product_id'        => 'required|exists:products,id',
            'sales_order_id'    => 'nullable|exists:sales_orders,id',
            'bom_id'            => 'nullable|exists:boms,id',
            'routing_id'        => 'nullable|exists:routings,id',
            'planned_qty'       => 'required|numeric|min:0.01',
            'start_date'        => 'required|date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            'priority'          => 'required|in:low,normal,high,urgent',
            'notes'             => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $wo = WorkOrder::create([
                'wo_number'      => $validated['wo_number'],
                'product_id'     => $validated['product_id'],
                'sales_order_id' => $validated['sales_order_id'] ?? null,
                'bom_id'         => $validated['bom_id'] ?? null,
                'routing_id'     => $validated['routing_id'] ?? null,
                'planned_qty'    => $validated['planned_qty'],
                'start_date'     => $validated['start_date'],
                'end_date'       => $validated['end_date'] ?? null,
                'status'         => 'draft',
                'priority'       => $validated['priority'],
                'notes'          => $validated['notes'] ?? null,
                'created_by'     => auth()->id(),
            ]);

            DB::commit();

            return redirect()
                ->route('production.work-orders.show', $wo)
                ->with('success', "Work Order {$wo->wo_number} berhasil dibuat.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal membuat WO: ' . $e->getMessage());
        }
    }

    public function show(WorkOrder $workOrder)
    {
        $workOrder->load([
            'product', 'bom', 'routing', 'salesOrder',
            'materials.material', 'progress.workCenter',
            'materialIssues', 'creator', 'approver',
        ]);

        return view('production.work-orders.show', [
            'item' => $workOrder,
        ]);
    }

    public function edit(WorkOrder $workOrder)
    {
        if (!$workOrder->canEdit()) {
            return redirect()
                ->route('production.work-orders.show', $workOrder)
                ->with('error', 'Work Order yang sudah di-release tidak bisa diedit.');
        }

        return view('production.work-orders.edit', [
            'item'     => $workOrder,
            'products' => Product::active()->orderBy('nama')->get(),
            'boms'     => Bom::where('status', 'active')->with('product')->orderByDesc('effective_date')->get(),
            'routings' => Routing::where('status', 'active')->with('product')->orderByDesc('effective_date')->get(),
        ]);
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        if (!$workOrder->canEdit()) {
            return back()->with('error', 'Work Order yang sudah di-release tidak bisa diedit.');
        }

        $validated = $request->validate([
            'wo_number'   => 'required|string|max:50|unique:work_orders,wo_number,' . $workOrder->id,
            'product_id'  => 'required|exists:products,id',
            'bom_id'      => 'nullable|exists:boms,id',
            'routing_id'  => 'nullable|exists:routings,id',
            'planned_qty' => 'required|numeric|min:0.01',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'priority'    => 'required|in:low,normal,high,urgent',
            'notes'       => 'nullable|string',
        ]);

        $workOrder->update($validated);

        return redirect()
            ->route('production.work-orders.show', $workOrder)
            ->with('success', 'Work Order berhasil diperbarui.');
    }

    public function destroy(WorkOrder $workOrder)
    {
        if (!$workOrder->canEdit()) {
            return back()->with('error', 'Hanya WO draft yang bisa dihapus.');
        }

        $number = $workOrder->wo_number;
        $workOrder->delete();

        return redirect()
            ->route('production.work-orders.index')
            ->with('success', "Work Order {$number} berhasil dihapus.");
    }

    // ============ ACTIONS ============

    public function release(WorkOrder $workOrder)
    {
        if (!$workOrder->canRelease()) {
            return back()->with('error', 'WO belum bisa di-release. Pastikan BOM sudah dipilih.');
        }

        try {
            WorkOrderService::release($workOrder);

            return redirect()
                ->route('production.work-orders.show', $workOrder)
                ->with('success', 'Work Order berhasil di-release. Material requirement sudah di-generate dari BOM.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal release WO: ' . $e->getMessage());
        }
    }

    public function start(WorkOrder $workOrder)
    {
        if (!$workOrder->canStart()) {
            return back()->with('error', 'WO harus di-release dulu sebelum mulai produksi.');
        }

        WorkOrderService::start($workOrder);

        return redirect()
            ->route('production.work-orders.show', $workOrder)
            ->with('success', 'Work Order mulai diproduksi.');
    }

    public function complete(WorkOrder $workOrder)
    {
        if (!$workOrder->canComplete()) {
            return back()->with('error', 'WO harus dalam status in_progress untuk diselesaikan.');
        }

        DB::beginTransaction();
        try {
            // Complete WO
            WorkOrderService::complete($workOrder);

            // Create cost snapshot
            CostingService::createSnapshot($workOrder->fresh());

            DB::commit();

            return redirect()
                ->route('production.work-orders.show', $workOrder)
                ->with('success', 'Work Order selesai. Cost snapshot sudah dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal complete WO: ' . $e->getMessage());
        }
    }

    public function cancel(WorkOrder $workOrder)
    {
        if (!$workOrder->canCancel()) {
            return back()->with('error', 'WO ini tidak bisa dibatalkan.');
        }

        WorkOrderService::cancel($workOrder);

        return redirect()
            ->route('production.work-orders.index')
            ->with('success', 'Work Order berhasil dibatalkan.');
    }

    public function recalculate(WorkOrder $workOrder)
    {
        try {
            WorkOrderService::recalculateCost($workOrder);

            return back()->with('success', 'Cost WO berhasil dihitung ulang.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal recalculate: ' . $e->getMessage());
        }
    }

    /**
     * Manual trigger untuk buat cost snapshot
     */
    public function snapshot(WorkOrder $workOrder)
    {
        try {
            CostingService::createSnapshot($workOrder);

            return back()->with('success', 'Cost snapshot berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal buat snapshot: ' . $e->getMessage());
        }
    }
}