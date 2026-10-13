<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\Sortable;

abstract class BaseCrudController extends Controller
    {
        protected string $model;
        protected string $viewPrefix;
        protected string $routePrefix;
        protected string $title;

        protected array $searchable = [];
        protected array $sortable = [];           // ← TAMBAH: whitelist kolom yang bisa di-sort
        protected array $validationRules = [];
        protected array $relations = [];
        protected int $perPage = 15;
        protected string $orderBy = 'latest';
        protected string $orderColumn = 'created_at';

    /**
     * List semua data
     */
    public function index(Request $request)
    {
        $query = $this->model::query();

        if (!empty($this->relations)) {
            $query->with($this->relations);
        }

        // Search
        if ($request->filled('search') && !empty($this->searchable)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                foreach ($this->searchable as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        // Order
        // Sorting (custom dari query string, atau default)
        $sortColumn = $request->get('sort', $this->orderColumn);
        $sortDirection = $request->get('direction', 'desc');

        // Whitelist kolom yang boleh di-sort
        $allowedSorts = $this->sortable ?? [$this->orderColumn];
        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = $this->orderColumn;
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortColumn, $sortDirection);

        $items = $query->paginate($this->perPage);

        return view("{$this->viewPrefix}.index", [
            'items'       => $items,
            'title'       => $this->title,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    /**
     * Form create
     */
    public function create()
    {
        return view("{$this->viewPrefix}.create", [
            'title'       => $this->title,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    /**
     * Simpan data baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules);

        $this->model::create($validated);

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', "{$this->title} berhasil ditambahkan.");
    }

    /**
     * Detail
     */
    public function show(string $id)
    {
        $item = $this->model::findOrFail($id);

        return view("{$this->viewPrefix}.show", [
            'item'        => $item,
            'title'       => $this->title,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    /**
     * Form edit
     */
    public function edit(string $id)
    {
        $item = $this->model::findOrFail($id);

        return view("{$this->viewPrefix}.edit", [
            'item'        => $item,
            'title'       => $this->title,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    /**
     * Update
     */
    public function update(Request $request, string $id)
    {
        $item = $this->model::findOrFail($id);

        $rules = $this->validationRules;

        // Replace unique rule untuk update (exclude current ID)
        foreach ($rules as $field => $rule) {
            if (is_string($rule) && Str::contains($rule, 'unique:')) {
                // Add ",{id}" to unique rule
                $rules[$field] = $rule . ',' . $item->id;
            }
        }

        $validated = $request->validate($rules);

        $item->update($validated);

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', "{$this->title} berhasil diperbarui.");
    }

    /**
     * Hapus data — dengan error handling yang jelas
     */
    public function destroy(string $id)
    {
        $item = $this->model::findOrFail($id);

        // Cek apakah model punya method canDelete()
        if (method_exists($item, 'canDelete')) {
            $check = $item->canDelete();
            if ($check !== true) {
                return back()->with('error', $check);
            }
        }

        try {
            $item->delete();

            return redirect()
                ->route("{$this->routePrefix}.index")
                ->with('success', "{$this->title} berhasil dihapus.");

        } catch (\Illuminate\Database\QueryException $e) {
            // Cek error foreign key
            $isForeignKeyError = str_contains($e->getMessage(), 'FOREIGN KEY')
                || str_contains($e->getMessage(), 'Integrity constraint')
                || $e->getCode() == '23000';

            if ($isForeignKeyError) {
                $referensi = $this->getRelatedReferences($id);

                $message = "❌ Tidak bisa hapus {$this->title} ini karena masih digunakan di data lain.";

                if (!empty($referensi)) {
                    $message .= " Referensi yang ditemukan: " . implode(', ', $referensi) . ".";
                }

                $message .= " Silakan nonaktifkan saja (uncheck 'Aktif') atau hapus referensi terlebih dahulu.";

                return back()->with('error', $message);
            }

            // Error lain
            return back()->with('error', "Gagal menghapus {$this->title}: " . $e->getMessage());
        }
    }

    /**
     * Cek referensi yang menghalangi delete
     */
    protected function getRelatedReferences(string $id): array
    {
        $references = [];
        $modelClass = get_class($this->model::find($id));

        // Cek BOM Items (khusus untuk Material & Product)
        if (str_contains($modelClass, 'Material')) {
            try {
                $count = \App\Models\Engineering\BomItem::where('item_type', 'material')
                    ->where('item_id', $id)
                    ->count();
                if ($count > 0) $references[] = "{$count} BOM Item";

                $count = \App\Models\Production\WorkOrderMaterial::where('material_id', $id)->count();
                if ($count > 0) $references[] = "{$count} Work Order Material";

                $count = \App\Models\Warehouse\Inventory::where('item_type', 'material')
                    ->where('item_id', $id)
                    ->count();
                if ($count > 0) $references[] = "{$count} Inventory";

                $count = \App\Models\Warehouse\StockMovement::where('item_type', 'material')
                    ->where('item_id', $id)
                    ->count();
                if ($count > 0) $references[] = "{$count} Stock Movement";

                $count = \App\Models\Purchasing\GoodsReceiptItem::where('material_id', $id)->count();
                if ($count > 0) $references[] = "{$count} Goods Receipt Item";

                $count = \App\Models\Purchasing\PurchaseOrderItem::where('material_id', $id)->count();
                if ($count > 0) $references[] = "{$count} Purchase Order Item";

                $count = \App\Models\Purchasing\PurchaseRequisitionItem::where('material_id', $id)->count();
                if ($count > 0) $references[] = "{$count} Purchase Requisition Item";

            } catch (\Exception $e) {
                // Skip kalau tabel tidak ada
            }
        }

        if (str_contains($modelClass, 'Product')) {
            try {
                $count = \App\Models\Engineering\BomItem::where('item_type', 'product')
                    ->where('item_id', $id)
                    ->count();
                if ($count > 0) $references[] = "{$count} BOM Item (sebagai sub-assembly)";

                $count = \App\Models\Production\WorkOrder::where('product_id', $id)->count();
                if ($count > 0) $references[] = "{$count} Work Order";

                $count = \App\Models\Warehouse\Inventory::where('item_type', 'product')
                    ->where('item_id', $id)
                    ->count();
                if ($count > 0) $references[] = "{$count} Inventory";
            } catch (\Exception $e) {
                // Skip
            }
        }

        if (str_contains($modelClass, 'Bom')) {
            try {
                $count = \App\Models\Production\WorkOrder::where('bom_id', $id)->count();
                if ($count > 0) $references[] = "{$count} Work Order";
            } catch (\Exception $e) {
                // Skip
            }
        }

        if (str_contains($modelClass, 'Routing')) {
            try {
                $count = \App\Models\Production\WorkOrder::where('routing_id', $id)->count();
                if ($count > 0) $references[] = "{$count} Work Order";
            } catch (\Exception $e) {
                // Skip
            }
        }

        if (str_contains($modelClass, 'Warehouse')) {
            try {
                $count = \App\Models\Warehouse\Location::where('warehouse_id', $id)->count();
                if ($count > 0) $references[] = "{$count} Location";
            } catch (\Exception $e) {
                // Skip
            }
        }

        if (str_contains($modelClass, 'Supplier')) {
            try {
                $count = \App\Models\Purchasing\PurchaseOrder::where('supplier_id', $id)->count();
                if ($count > 0) $references[] = "{$count} Purchase Order";

                $count = \App\Models\Master\Material::where('supplier_id', $id)->count();
                if ($count > 0) $references[] = "{$count} Material";
            } catch (\Exception $e) {
                // Skip
            }
        }

        return $references;
    }

    /**
     * Apply sorting dari query string
     * 
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySorting($query, Request $request)
    {
        $sortColumn = $request->get('sort', $this->orderColumn);
        $sortDirection = $request->get('direction', 'desc');

        // Whitelist kolom yang boleh di-sort
        $allowedSorts = $this->sortable ?? [$this->orderColumn];
        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = $this->orderColumn;
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        return $query->orderBy($sortColumn, $sortDirection);
    }
}