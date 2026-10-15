<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\BaseCrudController;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends BaseCrudController
{
    protected string $model = \Spatie\Permission\Models\Role::class;
    protected string $viewPath = 'settings.roles'; 

    public function index(Request $request)
    {
        $query = Role::withCount(['permissions', 'users']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $roles = $query->paginate(10)->withQueryString();

        return view('settings.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(function($permission) {
            return explode('.', $permission->name)[0] ?? 'General'; 
        });

        return view('settings.roles.create', compact('permissions'));
    }

    // ✅ DIPERBAIKI: Menggunakan Request standar agar kompatibel dengan BaseCrudController
    public function store(Request $request)
    {
        // Validasi Manual (menggantikan RoleFormRequest)
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'name.required' => 'Nama role wajib diisi.',
            'name.unique' => 'Nama role sudah digunakan.',
            'permissions.*.exists' => 'Ada permission yang tidak valid.',
        ]);

        $role = Role::create(['name' => $validated['name']]);
        
        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('settings.roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        $role = Role::with(['permissions', 'users'])->findOrFail($id);
        return view('settings.roles.show', compact('role'));
    }

    public function edit(string $id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::orderBy('name')->get()->groupBy(function($permission) {
            return explode('.', $permission->name)[0] ?? 'General';
        });

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('settings.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    // ✅ DIPERBAIKI: Menggunakan Request standar
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'name.required' => 'Nama role wajib diisi.',
            'name.unique' => 'Nama role sudah digunakan.',
        ]);

        $role = Role::findOrFail($id);
        $role->update(['name' => $validated['name']]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()->route('settings.roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        // SAFETY GUARD 1: Tidak bisa hapus role Owner
        if (strtolower($role->name) === 'owner') {
            return back()->with('error', 'Role Owner tidak dapat dihapus.');
        }

        // SAFETY GUARD 2: Tidak bisa hapus role yang masih dipakai user
        if ($role->users()->count() > 0) {
            return back()->with('error', "Role '{$role->name}' masih digunakan oleh user. Pindahkan user terlebih dahulu.");
        }

        $role->delete();

        return redirect()->route('settings.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }
}