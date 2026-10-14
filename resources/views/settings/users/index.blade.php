<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">User Management</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="SETTINGS"
            title="User Management"
            subtitle="Kelola pengguna sistem dan hak akses role"
            action="Tambah User"
            :actionUrl="route('settings.users.create')"
        />

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        {{-- Card Search & Filter --}}
        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('settings.users.index') }}" class="flex flex-wrap items-center gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nama atau email pengguna..."
                            class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                        >
                    </div>

                    <div class="w-44">
                        <select name="role" class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                            <option value="">-- Semua Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-36">
                        <select name="status" class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                            <option value="">-- Status --</option>
                            <option value="active" @selected(request('status') === 'active')>Aktif</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                        </select>
                    </div>

                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>

                    @if(request()->hasAny(['search', 'role', 'status']))
                        <x-atelier.button :href="route('settings.users.index')" variant="ghost">Reset</x-atelier.button>
                    @endif
                </form>
            </div>

            @if($users->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-navy-950/60 border-b border-navy-800">
                            <tr>
                                <x-sortable-header column="name" label="Pengguna" />
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Role</th>
                                <x-sortable-header column="is_active" label="Status" />
                                <x-sortable-header column="created_at" label="Terdaftar" />
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800/80">
                            @foreach($users as $user)
                                <tr class="hover:bg-navy-800/30 transition">
                                    {{-- Kolom Pengguna --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-gold-500/20 to-navy-800 border border-gold-500/30 flex items-center justify-center font-bold text-sm text-gold-400 flex-shrink-0">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <a href="{{ route('settings.users.show', $user) }}" class="text-sm font-medium text-white hover:text-gold-400 transition">
                                                    {{ $user->name }}
                                                </a>
                                                <p class="text-xs text-navy-400 font-mono">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Kolom Roles --}}
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            @forelse($user->roles as $role)
                                                @php
                                                    $badgeClass = match($role->name) {
                                                        'Owner'      => 'bg-gold-500/10 text-gold-400 border-gold-500/30',
                                                        'Admin'      => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                                                        'Manager'    => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                                                        'Sales'      => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                                        'PPIC'       => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30',
                                                        'Purchasing' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                                                        'Warehouse'  => 'bg-orange-500/10 text-orange-400 border-orange-500/30',
                                                        'Produksi'   => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30',
                                                        'QC'         => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                                                        'Finance'    => 'bg-teal-500/10 text-teal-400 border-teal-500/30',
                                                        default      => 'bg-navy-800 text-navy-300 border-navy-700',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium border {{ $badgeClass }}">
                                                    {{ $role->name }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-navy-500 italic">Tanpa Role</span>
                                            @endforelse
                                        </div>
                                    </td>

                                    {{-- Kolom Status --}}
                                    <td class="px-5 py-4">
                                        <form method="POST" action="{{ route('settings.users.toggle-status', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button 
                                                type="submit" 
                                                onclick="return confirm('Apakah Anda yakin ingin mengubah status aktif pengguna ini?')"
                                                class="group inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border transition {{ $user->is_active ? 'bg-green-500/10 border-green-500/30 text-green-400 hover:bg-green-500/20' : 'bg-navy-800 border-navy-700 text-navy-400 hover:bg-navy-700' }}"
                                                title="Klik untuk toggle status"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-green-400' : 'bg-navy-500' }}"></span>
                                                <span>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                            </button>
                                        </form>
                                    </td>

                                    {{-- Kolom Terdaftar --}}
                                    <td class="px-5 py-4 text-xs font-mono text-navy-400">
                                        {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}
                                    </td>

                                    {{-- Kolom Aksi --}}
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Detail --}}
                                            <a href="{{ route('settings.users.show', $user) }}" 
                                               class="p-1.5 text-navy-400 hover:text-white hover:bg-navy-800 rounded-lg transition"
                                               title="Detail Pengguna">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>

                                            {{-- Edit --}}
                                            <a href="{{ route('settings.users.edit', $user) }}" 
                                               class="p-1.5 text-navy-400 hover:text-gold-400 hover:bg-navy-800 rounded-lg transition"
                                               title="Edit Pengguna">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>

                                            {{-- Delete --}}
                                            @if(auth()->id() !== $user->id)
                                                <form method="POST" action="{{ route('settings.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna {{ $user->name }}? Aksi ini tidak dapat dibatalkan.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 text-navy-400 hover:text-red-400 hover:bg-navy-800 rounded-lg transition" title="Hapus Pengguna">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($users->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">
                        {{ $users->links() }}
                    </div>
                @endif
            @else
                <div class="p-12">
                    <x-atelier.empty-state
                        title="Pengguna Tidak Ditemukan"
                        message="Tidak ada data pengguna yang sesuai dengan kriteria pencarian Anda."
                    />
                </div>
            @endif
        </x-atelier.card>

    </div>
</x-app-layout>
