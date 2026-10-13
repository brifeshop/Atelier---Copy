<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\BaseCrudController;
use App\Models\Warehouse\Location;
use App\Models\Warehouse\Warehouse;
use App\Traits\BulkStoreable;
use Illuminate\Http\Request;

class LocationController extends BaseCrudController
{
    use BulkStoreable;

    protected string $model = Location::class;
    protected string $viewPrefix = 'warehouse.locations';
    protected string $routePrefix = 'warehouse.locations';
    protected string $title = 'Location';

    protected array $searchable = ['kode', 'nama', 'notes'];
    protected array $sortable = ['kode', 'nama', 'capacity', 'is_active', 'created_at'];

    protected array $validationRules = [
        'warehouse_id' => 'required|exists:warehouses,id',
        'kode'         => 'nullable|string|max:50|unique:locations,kode',
        'nama'         => 'required|string|max:255',
        'capacity'     => 'nullable|numeric|min:0',
        'notes'        => 'nullable|string',
        'is_active'    => 'boolean',
    ];

    public function index(Request $request)
    {
        $query = Location::with('warehouse');

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                foreach ($this->searchable as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        // Sorting — via helper
        $this->applySorting($query, $request);

        $items = $query->paginate(15);

        return view("{$this->viewPrefix}.index", [
            'items'       => $items,
            'title'       => $this->title,
            'routePrefix' => $this->routePrefix,
            'warehouses'  => Warehouse::active()->orderBy('nama')->get(),
        ]);
    }

    public function create()
    {
        $warehouses = Warehouse::active()->orderBy('nama')->get();
        $selectedWarehouseId = request()->input('warehouse_id');

        $generatedKode = '';
        if ($selectedWarehouseId) {
            $generatedKode = Location::generateKode((int) $selectedWarehouseId);
        }

        return view("{$this->viewPrefix}.create", [
            'title'               => $this->title,
            'routePrefix'         => $this->routePrefix,
            'warehouses'          => $warehouses,
            'generatedKode'       => $generatedKode,
            'selectedWarehouseId' => $selectedWarehouseId,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules);

        if (empty($validated['kode'])) {
            $validated['kode'] = Location::generateKode((int) $validated['warehouse_id']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Location::create($validated);

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', "{$this->title} berhasil ditambahkan.");
    }

    public function edit(string $id)
    {
        $item = Location::findOrFail($id);
        $warehouses = Warehouse::active()->orderBy('nama')->get();

        return view("{$this->viewPrefix}.edit", [
            'item'        => $item,
            'title'       => $this->title,
            'routePrefix' => $this->routePrefix,
            'warehouses'  => $warehouses,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $location = Location::findOrFail($id);

        $rules = $this->validationRules;
        $rules['kode'] = 'required|string|max:50|unique:locations,kode,' . $location->id;

        $validated = $request->validate($rules);

        $validated['is_active'] = $request->boolean('is_active', true);

        $location->update($validated);

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', "{$this->title} berhasil diperbarui.");
    }

    // ============ BULK INPUT ============

    /**
     * Form bulk — copy-paste dari Excel
     */
    public function bulkForm()
    {
        return view('warehouse.locations.bulk', [
            'warehouses' => Warehouse::active()->orderBy('nama')->get(),
        ]);
    }

    /**
     * Proses bulk store — pakai trait universal
     */
    public function bulkStore(Request $request)
        {
            return $this->bulkStoreGeneric(
                request: $request,
                modelClass: Location::class,
                rules: [
                    'kode'     => 'nullable|string|max:50',  // ← TAMBAH kode
                    'nama'     => 'required|string|max:255',
                    'capacity' => 'nullable|numeric|min:0',
                    'notes'    => 'nullable|string|max:500',
                ],
                beforeCreate: function ($data, $request) {
                    // Auto-generate kalau kode kosong
                    $kode = !empty($data['kode']) 
                            ? $data['kode'] 
                            : Location::generateKode((int) $request->warehouse_id);

                    // Cek duplikat kalau user isi kode custom
                    if (!empty($data['kode'])) {
                        $exists = Location::where('kode', $data['kode'])->exists();
                        if ($exists) {
                            throw new \Exception("Kode '{$data['kode']}' sudah ada.");
                        }
                    }

                    return array_merge($data, [
                        'warehouse_id' => $request->warehouse_id,
                        'kode'         => $kode,
                        'is_active'    => true,
                    ]);
                },
                uniqueCheck: function ($data, $request) {
                    return Location::where('warehouse_id', $request->warehouse_id)
                        ->where('nama', $data['nama'])
                        ->exists();
                },
                redirectRoute: 'warehouse.locations.index',
            );
        }
}