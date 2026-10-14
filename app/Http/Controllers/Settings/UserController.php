<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): View
    {
        $query = User::with('roles');

        // Pencarian nama atau email
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter role
        if ($request->filled('role')) {
            $role = $request->role;
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Filter status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['name', 'email', 'is_active', 'created_at'];

        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'created_at';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $users = $query->orderBy($sortColumn, $sortDirection)->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('settings.users.index', compact('users', 'roles', 'sortColumn', 'sortDirection'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $roles = Role::orderBy('name')->get();
        return view('settings.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        // Privilege escalation guard: Hanya Owner yang bisa membuat user dengan role Owner
        if (in_array('Owner', $request->roles) && !auth()->user()->hasRole('Owner')) {
            return back()->with('error', 'Hanya Owner yang dapat memberikan hak akses (role) Owner ke pengguna baru.')->withInput();
        }

        $userData = [
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password, // Password di-hash otomatis via cast 'hashed' pada User model
            'is_active' => $request->boolean('is_active', true),
        ];

        $user = User::create($userData);
        $user->syncRoles($request->roles);

        return redirect()
            ->route('settings.users.index')
            ->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): View
    {
        $user->load(['roles.permissions', 'permissions']);

        // Kumpulkan semua permission efektif yang dimiliki user
        $allPermissions = $user->getAllPermissions()->sortBy('name')->groupBy(function ($permission) {
            $parts = explode(' ', $permission->name);
            return count($parts) > 1 ? end($parts) : 'general';
        });

        return view('settings.users.show', compact('user', 'allPermissions'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View|RedirectResponse
    {
        // Privilege escalation guard: Non-owner tidak boleh mengedit akun Owner
        if ($user->hasRole('Owner') && !auth()->user()->hasRole('Owner')) {
            return redirect()
                ->route('settings.users.index')
                ->with('error', 'Anda tidak memiliki wewenang untuk mengubah data akun Owner.');
        }

        $roles = Role::orderBy('name')->get();
        $userRoles = $user->roles->pluck('name')->toArray();

        return view('settings.users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Privilege escalation guard: Non-owner tidak boleh mengubah akun Owner
        if ($user->hasRole('Owner') && !auth()->user()->hasRole('Owner')) {
            return back()->with('error', 'Anda tidak memiliki wewenang untuk mengubah data akun Owner.');
        }

        // Privilege escalation guard: Non-owner tidak boleh assign role Owner
        if (in_array('Owner', $request->roles) && !auth()->user()->hasRole('Owner')) {
            return back()->with('error', 'Hanya Owner yang dapat memberikan hak akses Owner.')->withInput();
        }

        // Sole-owner guard: Jika user adalah Owner, cek apakah role Owner akan dicabut
        if ($user->hasRole('Owner') && !in_array('Owner', $request->roles)) {
            $activeOwnerCount = User::role('Owner')->where('is_active', true)->count();
            if ($activeOwnerCount <= 1) {
                return back()->with('error', 'Tidak dapat mencabut role Owner dari satu-satunya akun Owner yang aktif.')->withInput();
            }
        }

        // Sole-owner guard: Jika user adalah Owner, jangan boleh dinonaktifkan jika dia owner aktif terakhir
        if ($user->hasRole('Owner') && $request->has('is_active') && !$request->boolean('is_active')) {
            $activeOwnerCount = User::role('Owner')->where('is_active', true)->count();
            if ($activeOwnerCount <= 1) {
                return back()->with('error', 'Tidak dapat menonaktifkan satu-satunya akun Owner yang aktif.')->withInput();
            }
        }

        $userData = [
            'name'      => $request->name,
            'email'     => $request->email,
            'is_active' => $request->boolean('is_active', true),
        ];

        // Update password hanya jika diisi
        if ($request->filled('password')) {
            $userData['password'] = $request->password;
        }

        $user->update($userData);
        $user->syncRoles($request->roles);

        return redirect()
            ->route('settings.users.index')
            ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Self-delete guard
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Privilege escalation guard: Non-owner tidak boleh menghapus akun Owner
        if ($user->hasRole('Owner') && !auth()->user()->hasRole('Owner')) {
            return back()->with('error', 'Hanya Owner yang berhak menghapus akun Owner.');
        }

        // Sole-admin / sole-owner guard
        if ($user->hasRole('Owner')) {
            $ownerCount = User::role('Owner')->count();
            if ($ownerCount <= 1) {
                return back()->with('error', 'Tidak dapat menghapus satu-satunya akun Owner di sistem.');
            }
        }

        try {
            $userName = $user->name;
            $user->delete();

            return redirect()
                ->route('settings.users.index')
                ->with('success', "Pengguna {$userName} berhasil dihapus.");

        } catch (QueryException $e) {
            // Error integrity constraint foreign key
            $isForeignKeyError = str_contains($e->getMessage(), 'FOREIGN KEY')
                || str_contains($e->getMessage(), 'Integrity constraint')
                || $e->getCode() == '23000';

            if ($isForeignKeyError) {
                return back()->with('error', '❌ Pengguna ini tidak dapat dihapus karena tercatat pada riwayat transaksi sistem. Silakan nonaktifkan status akunnya saja.');
            }

            return back()->with('error', 'Gagal menghapus pengguna: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active/inactive status of the user.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        // Self-deactivation guard
        if (auth()->id() === $user->id && $user->is_active) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        // Sole-owner guard
        if ($user->is_active && $user->hasRole('Owner')) {
            $activeOwnerCount = User::role('Owner')->where('is_active', true)->count();
            if ($activeOwnerCount <= 1) {
                return back()->with('error', 'Tidak dapat menonaktifkan satu-satunya akun Owner yang aktif.');
            }
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Status akun {$user->name} berhasil {$statusText}.");
    }
}
