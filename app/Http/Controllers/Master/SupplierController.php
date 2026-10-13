<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\BaseCrudController;
use App\Models\Master\Supplier;
use Illuminate\Http\Request;

class SupplierController extends BaseCrudController
{
    protected string $model = Supplier::class;
    protected string $viewPrefix = 'master.suppliers';
    protected string $routePrefix = 'master.suppliers';
    protected string $title = 'Supplier';

    protected array $searchable = ['kode', 'nama', 'pic_name', 'phone', 'email', 'city'];
protected array $sortable = ['kode', 'nama', 'pic_name', 'city', 'rating', 'created_at'];

    protected array $validationRules = [
        'kode' => 'required|string|max:50|unique:suppliers,kode',
        'nama' => 'required|string|max:255',
        'pic_name' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:50',
        'email' => 'nullable|email|max:255',
        'address' => 'nullable|string',
        'city' => 'nullable|string|max:100',
        'bank_account' => 'nullable|string|max:100',
        'payment_terms' => 'nullable|string|max:100',
        'rating' => 'nullable|integer|min:1|max:5',
        'notes' => 'nullable|string',
        'is_active' => 'boolean',
    ];

    public function create()
    {
        return view("{$this->viewPrefix}.create", [
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'generatedKode' => Supplier::generateKode(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules);

        if (empty($validated['kode'])) {
            $validated['kode'] = Supplier::generateKode();
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Supplier::create($validated);

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', "{$this->title} berhasil ditambahkan.");
    }

    public function update(Request $request, string $id)
    {
        $supplier = Supplier::findOrFail($id);

        $rules = $this->validationRules;
        $rules['kode'] = 'required|string|max:50|unique:suppliers,kode,' . $supplier->id;

        $validated = $request->validate($rules);

        $validated['is_active'] = $request->boolean('is_active', true);

        $supplier->update($validated);

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', "{$this->title} berhasil diperbarui.");
    }
}