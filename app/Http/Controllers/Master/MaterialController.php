<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\BaseCrudController;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Helpers\UploadHelper;
use App\Traits\BulkStoreable;
use Illuminate\Http\Request;

class MaterialController extends BaseCrudController
{
    use BulkStoreable;

    protected string $model = Material::class;
    protected string $viewPrefix = 'master.materials';
    protected string $routePrefix = 'master.materials';
    protected string $title = 'Material';

    // ← TAMBAH kode_bahan & spesifikasi
    protected array $searchable = ['kode', 'kode_bahan', 'nama', 'spesifikasi', 'category', 'unit', 'location'];
    protected array $sortable = ['kode', 'kode_bahan', 'nama', 'category', 'unit', 'price', 'current_stock', 'created_at'];

    protected array $validationRules = [
        'kode'              => 'required|string|max:50|unique:materials,kode',
        'kode_bahan'        => 'nullable|string|max:50',                       // ← TAMBAH
        'nama'              => 'required|string|max:255',
        'spesifikasi'       => 'nullable|string',                               // ← TAMBAH
        'category'          => 'nullable|string|max:100',
        'unit'              => 'required|string|max:20',

        // ← DIMENSI STANDAR (untuk 5 costing method)
        'panjang_standar'   => 'nullable|numeric|min:0',
        'lebar_standar'     => 'nullable|numeric|min:0',
        'tinggi_standar'    => 'nullable|numeric|min:0',
        'berat_standar'     => 'nullable|numeric|min:0',
        'volume_standar'    => 'nullable|numeric|min:0',

        // ← COSTING
        'costing_method'    => 'required|in:per_unit,per_area,per_volume,per_length,per_weight',
        'base_unit'         => 'nullable|string|max:20',
        'yield_percent'     => 'nullable|numeric|min:0|max:100',

        'price'             => 'nullable|numeric|min:0',
        'min_stock'         => 'nullable|numeric|min:0',
        'max_stock'         => 'nullable|numeric|min:0',
        'current_stock'     => 'nullable|numeric|min:0',
        'supplier_id'       => 'nullable|exists:suppliers,id',
        'location'          => 'nullable|string|max:100',
        'notes'             => 'nullable|string',
        'photo'             => 'nullable|image|max:2048',
        'is_active'         => 'boolean',
    ];

    public function index(Request $request)
    {
        $query = Material::with('supplier');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                foreach ($this->searchable as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        // Filter by costing method
        if ($request->filled('costing_method')) {
            $query->where('costing_method', $request->costing_method);
        }

        // Sorting — via helper
        $this->applySorting($query, $request);

        $items = $query->paginate(15);

        return view("{$this->viewPrefix}.index", [
            'items'         => $items,
            'title'         => $this->title,
            'routePrefix'   => $this->routePrefix,
        ]);
    }

    public function create()
    {
        return view("{$this->viewPrefix}.create", [
            'title'         => $this->title,
            'routePrefix'   => $this->routePrefix,
            'generatedKode' => Material::generateKode(),
            'suppliers'     => Supplier::active()->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules);

        if (empty($validated['kode'])) {
            $validated['kode'] = Material::generateKode();
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = UploadHelper::uploadPhoto(
                $request->file('photo'),
                'materials',
                $validated['kode']
            );
        }

        unset($validated['photo']);

        // Default values untuk costing
        $validated['yield_percent'] = $validated['yield_percent'] ?? 100;

        // Reset dimensi kalau costing_method = per_unit
        if (($validated['costing_method'] ?? 'per_unit') === 'per_unit') {
            $validated['panjang_standar'] = null;
            $validated['lebar_standar'] = null;
            $validated['tinggi_standar'] = null;
            $validated['berat_standar'] = null;
            $validated['volume_standar'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Material::create($validated);

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', "{$this->title} berhasil ditambahkan.");
    }

    public function edit(string $id)
    {
        $item = Material::findOrFail($id);

        return view("{$this->viewPrefix}.edit", [
            'item'        => $item,
            'title'       => $this->title,
            'routePrefix' => $this->routePrefix,
            'suppliers'   => Supplier::active()->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $material = Material::findOrFail($id);

        $rules = $this->validationRules;
        $rules['kode'] = 'required|string|max:50|unique:materials,kode,' . $material->id;

        $validated = $request->validate($rules);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = UploadHelper::uploadPhoto(
                $request->file('photo'),
                'materials',
                $validated['kode'],
                $material->photo_path
            );
        }

        unset($validated['photo']);

        // Default values untuk costing
        $validated['yield_percent'] = $validated['yield_percent'] ?? 100;

        // Reset dimensi kalau costing_method = per_unit
        if (($validated['costing_method'] ?? 'per_unit') === 'per_unit') {
            $validated['panjang_standar'] = null;
            $validated['lebar_standar'] = null;
            $validated['tinggi_standar'] = null;
            $validated['berat_standar'] = null;
            $validated['volume_standar'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $material->update($validated);

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', "{$this->title} berhasil diperbarui.");
    }

    public function destroy(string $id)
    {
        $material = Material::findOrFail($id);

        try {
            $material->delete();

            return redirect()
                ->route('master.materials.index')
                ->with('success', 'Material berhasil dihapus.');

        } catch (\Illuminate\Database\QueryException $e) {
            $isForeignKeyError = str_contains($e->getMessage(), 'FOREIGN KEY')
                || str_contains($e->getMessage(), 'Integrity constraint')
                || $e->getCode() == '23000';

            if ($isForeignKeyError) {
                $referensi = [];

                $count = \App\Models\Engineering\BomItem::where('item_type', 'material')->where('item_id', $id)->count();
                if ($count > 0) $referensi[] = "{$count} BOM Item";

                $count = \App\Models\Production\WorkOrderMaterial::where('material_id', $id)->count();
                if ($count > 0) $referensi[] = "{$count} Work Order Material";

                $count = \App\Models\Warehouse\Inventory::where('item_type', 'material')->where('item_id', $id)->count();
                if ($count > 0) $referensi[] = "{$count} Inventory";

                $count = \App\Models\Warehouse\StockMovement::where('item_type', 'material')->where('item_id', $id)->count();
                if ($count > 0) $referensi[] = "{$count} Stock Movement";

                $message = "❌ Tidak bisa hapus material <strong>{$material->nama}</strong> karena masih digunakan di: <br>";
                $message .= "• " . implode("<br>• ", $referensi);
                $message .= "<br><br>💡 <strong>Saran:</strong> Nonaktifkan material (uncheck 'Material Aktif' di halaman edit) untuk menyembunyikannya tanpa menghapus data historis.";

                return back()->with('error', $message);
            }

            return back()->with('error', 'Gagal menghapus material: ' . $e->getMessage());
        }
    }

    /**
     * Quick store material dari halaman BOM
     * 
     * POST /master/materials/quick-store
     */
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'nama'              => 'required|string|max:255',
            'kode_bahan'        => 'nullable|string|max:50',
            'category'          => 'nullable|string|max:100',
            'unit'              => 'required|string|max:20',
            'costing_method'    => 'required|in:per_unit,per_area,per_volume,per_length,per_weight',
            'volume_type'       => 'nullable|in:kotak,cair',
            'panjang_standar'   => 'nullable|numeric|min:0',
            'lebar_standar'     => 'nullable|numeric|min:0',
            'tinggi_standar'    => 'nullable|numeric|min:0',
            'berat_standar'     => 'nullable|numeric|min:0',
            'volume_standar'    => 'nullable|numeric|min:0',
            'price'             => 'required|numeric|min:0',
            'yield_percent'     => 'nullable|numeric|min:0|max:100',
            'location'          => 'nullable|string|max:100',
        ]);

        try {
            $validated['kode'] = Material::generateKode();
            $validated['is_active'] = true;
            $validated['yield_percent'] = $validated['yield_percent'] ?? 100;

            // Reset dimensi kalau per_unit
            if ($validated['costing_method'] === 'per_unit') {
                $validated['panjang_standar'] = null;
                $validated['lebar_standar'] = null;
                $validated['tinggi_standar'] = null;
                $validated['berat_standar'] = null;
                $validated['volume_standar'] = null;
                $validated['volume_type'] = null;
            }

            // Reset volume cair kalau bukan per_volume
            if ($validated['costing_method'] !== 'per_volume') {
                $validated['volume_type'] = null;
                $validated['volume_standar'] = null;
            }

            $material = Material::create($validated);

            return response()->json([
                'success' => true,
                'material' => [
                    'id' => $material->id,
                    'kode' => $material->kode,
                    'kode_bahan' => $material->kode_bahan,
                    'nama' => $material->nama,
                    'spesifikasi' => $material->spesifikasi,
                    'unit' => $material->unit,
                    'costing_method' => $material->costing_method,
                    'volume_type' => $material->volume_type,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal simpan material: ' . $e->getMessage(),
            ], 500);
        }
    }
    // ============ BULK INPUT ============

    /**
     * Form bulk — copy-paste dari Excel
     */
    public function bulkForm()
    {
        return view('master.materials.bulk');
    }

    /**
     * Proses bulk store — pakai trait universal
     */
    public function bulkStore(Request $request)
    {
        return $this->bulkStoreGeneric(
            request: $request,
            modelClass: Material::class,
            rules: [
                'kode'              => 'nullable|string|max:50',
                'kode_bahan'        => 'nullable|string|max:50',
                'nama'              => 'required|string|max:255',
                'spesifikasi'       => 'nullable|string',
                'category'          => 'nullable|string|max:100',
                'unit'              => 'required|string|max:20',
                'costing_method'    => 'required|in:per_unit,per_area,per_volume,per_length,per_weight',
                'panjang_standar'   => 'nullable|numeric|min:0',
                'lebar_standar'     => 'nullable|numeric|min:0',
                'tinggi_standar'    => 'nullable|numeric|min:0',
                'berat_standar'     => 'nullable|numeric|min:0',
                'volume_standar'    => 'nullable|numeric|min:0',
                'volume_type'       => 'nullable|in:kotak,cair',
                'price'             => 'nullable|numeric|min:0',
                'yield_percent'     => 'nullable|numeric|min:0|max:100',
                'min_stock'         => 'nullable|numeric|min:0',
                'max_stock'         => 'nullable|numeric|min:0',
                'location'          => 'nullable|string|max:100',
                'notes'             => 'nullable|string',
            ],
            beforeCreate: function ($data, $request) {
                // Auto-generate kode kalau kosong
                $kode = !empty($data['kode']) 
                        ? $data['kode'] 
                        : Material::generateKode();

                // Cek duplikat kode
                if (!empty($data['kode'])) {
                    $exists = Material::where('kode', $data['kode'])->exists();
                    if ($exists) {
                        throw new \Exception("Kode '{$data['kode']}' sudah ada.");
                    }
                }

                // Default values
                $data['yield_percent'] = $data['yield_percent'] ?? 100;

                // Reset dimensi kalau per_unit
                if (($data['costing_method'] ?? 'per_unit') === 'per_unit') {
                    $data['panjang_standar'] = null;
                    $data['lebar_standar'] = null;
                    $data['tinggi_standar'] = null;
                    $data['berat_standar'] = null;
                    $data['volume_standar'] = null;
                    $data['volume_type'] = null;
                }

                // Reset volume cair kalau bukan per_volume
                if (($data['costing_method'] ?? 'per_unit') !== 'per_volume') {
                    $data['volume_type'] = null;
                    $data['volume_standar'] = null;
                }

                return array_merge($data, [
                    'kode'      => $kode,
                    'is_active' => true,
                ]);
            },
            uniqueCheck: function ($data, $request) {
                return Material::where('nama', $data['nama'])->exists();
            },
            redirectRoute: 'master.materials.index',
        );
    }

}