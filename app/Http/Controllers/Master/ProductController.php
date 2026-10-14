<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\BaseCrudController;
use App\Models\Master\Product;
use App\Helpers\UploadHelper;
use App\Traits\BulkStoreable;   // ← TAMBAH
use Illuminate\Http\Request;

class ProductController extends BaseCrudController
{
    use BulkStoreable;   // ← TAMBAH

    protected string $model = Product::class;
    protected string $viewPrefix = 'master.products';
    protected string $routePrefix = 'master.products';
    protected string $title = 'Product';

    protected array $searchable = ['kode', 'nama', 'category', 'unit'];
    protected array $sortable = ['kode', 'nama', 'category', 'unit', 'selling_price', 'created_at'];

    protected array $validationRules = [
        'kode' => 'required|string|max:50|unique:products,kode',
        'nama' => 'required|string|max:255',
        'category' => 'nullable|string|max:100',
        'unit' => 'required|string|max:20',
        'description' => 'nullable|string',
        'selling_price' => 'nullable|numeric|min:0',
        'notes' => 'nullable|string',
        'photo' => 'nullable|image|max:2048',
        'is_active' => 'boolean',
    ];

    public function create()
    {
        return view("{$this->viewPrefix}.create", [
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'generatedKode' => Product::generateKode(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules);

        if (empty($validated['kode'])) {
            $validated['kode'] = Product::generateKode();
        }

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = UploadHelper::uploadPhoto(
                $request->file('photo'),
                'products',
                $validated['kode']
            );
        }

        unset($validated['photo']);

        $validated['is_active'] = $request->boolean('is_active', true);

        Product::create($validated);

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', "{$this->title} berhasil ditambahkan.");
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $rules = $this->validationRules;
        $rules['kode'] = 'required|string|max:50|unique:products,kode,' . $product->id;

        $validated = $request->validate($rules);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = UploadHelper::uploadPhoto(
                $request->file('photo'),
                'products',
                $validated['kode'],
                $product->photo_path
            );
        }

        unset($validated['photo']);

        $validated['is_active'] = $request->boolean('is_active', true);

        $product->update($validated);

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
        return view('master.products.bulk');
    }

    /**
     * Proses bulk store — pakai trait universal
     */
    public function bulkStore(Request $request)
    {
        return $this->bulkStoreGeneric(
            request: $request,
            modelClass: Product::class,
            rules: [
                'kode'          => 'nullable|string|max:50',
                'nama'          => 'required|string|max:255',
                'category'      => 'nullable|string|max:100',
                'unit'          => 'required|string|max:20',
                'description'   => 'nullable|string',
                'selling_price' => 'nullable|numeric|min:0',
                'notes'         => 'nullable|string',
            ],
            beforeCreate: function ($data, $request) {
                // Auto-generate kode kalau kosong
                $kode = !empty($data['kode']) 
                        ? $data['kode'] 
                        : Product::generateKode();

                // Cek duplikat kode
                if (!empty($data['kode'])) {
                    $exists = Product::where('kode', $data['kode'])->exists();
                    if ($exists) {
                        throw new \Exception("Kode '{$data['kode']}' sudah ada.");
                    }
                }

                return array_merge($data, [
                    'kode'      => $kode,
                    'is_active' => true,
                ]);
            },
            uniqueCheck: function ($data, $request) {
                return Product::where('nama', $data['nama'])->exists();
            },
            redirectRoute: 'master.products.index',
        );
    }
}