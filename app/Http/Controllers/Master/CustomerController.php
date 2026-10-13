<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\BaseCrudController;
use App\Models\Master\Customer;
use Illuminate\Http\Request;

class CustomerController extends BaseCrudController
{
    protected string $model = Customer::class;
    protected string $viewPrefix = 'master.customers';
    protected string $routePrefix = 'master.customers';
    protected string $title = 'Customer';

    protected array $searchable = ['kode', 'nama', 'contact_person', 'phone', 'email', 'city'];
protected array $sortable = ['kode', 'nama', 'contact_person', 'city', 'created_at'];

    protected array $validationRules = [
        'kode' => 'required|string|max:50|unique:customers,kode',
        'nama' => 'required|string|max:255',
        'contact_person' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:50',
        'email' => 'nullable|email|max:255',
        'address' => 'nullable|string',
        'city' => 'nullable|string|max:100',
        'payment_terms' => 'nullable|string|max:100',
        'notes' => 'nullable|string',
        'is_active' => 'boolean',
    ];

    /**
     * Override create: kirim kode otomatis ke view.
     */
    public function create()
    {
        return view("{$this->viewPrefix}.create", [
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'generatedKode' => Customer::generateKode(),
        ]);
    }

    /**
     * Override store: auto-generate kode jika kosong.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules);

        if (empty($validated['kode'])) {
            $validated['kode'] = Customer::generateKode();
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Customer::create($validated);

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', "{$this->title} berhasil ditambahkan.");
    }

    /**
     * Override update: handle checkbox is_active.
     */
    public function update(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);

        // Ubah rule unique agar ignore ID ini
        $rules = $this->validationRules;
        $rules['kode'] = 'required|string|max:50|unique:customers,kode,' . $customer->id;

        $validated = $request->validate($rules);

        $validated['is_active'] = $request->boolean('is_active', true);

        $customer->update($validated);

        return redirect()
            ->route("{$this->routePrefix}.index")
            ->with('success', "{$this->title} berhasil diperbarui.");
    }
}