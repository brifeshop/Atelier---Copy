<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Detail Pengguna: {{ $user->name }}</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="SETTINGS"
            title="Detail Pengguna"
            subtitle="Informasi profil akun dan rincian hak akses modul"
            action="Edit Pengguna"
            :actionUrl="route('settings.users.edit', $user)"
        />

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Kolom Kiri: Profil & Status --}}
            <div class="lg:col-span-1 space-y-6">
                <x-atelier.card :brackets="true">
                    <div class="text-center py-4">
                        <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-br from-gold-500/30 to-navy-800 border-2 border-gold-500 flex items-center justify-center font-serif text-3xl font-bold text-gold-400 shadow-lg shadow-gold-500/10 mb-4">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <h3 class="font-serif text-xl font-bold text-white">{{ $user->name }}</h3>
                        <p class="text-xs text-navy-400 font-mono mt-1">{{ $user->email }}</p>

                        <div class="mt-4 flex items-center justify-center gap-2">
                            @if($user->is_active)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-green-500/10 border border-green-500/30 text-green-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-navy-800 border border-navy-700 text-navy-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-navy-500"></span>Nonaktif
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="border-t border-navy-800 mt-6 pt-6 space-y-3.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-navy-400">ID Pengguna</span>
                            <span class="font-mono text-white">#{{ $user->id }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-navy-400">Tanggal Dibuat</span>
                            <span class="font-mono text-white">{{ $user->created_at ? $user->created_at->format('d M Y H:i') : '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-navy-400">Terakhir Diperbarui</span>
                            <span class="font-mono text-white">{{ $user->updated_at ? $user->updated_at->format('d M Y H:i') : '-' }}</span>
                        </div>
                    </div>

                    <div class="border-t border-navy-800 mt-6 pt-6 flex flex-col gap-2">
                        <form method="POST" action="{{ route('settings.users.toggle-status', $user) }}">
                            @csrf
                            @method('PATCH')
                            <button
                                type="submit"
                                onclick="return confirm('Apakah Anda yakin ingin mengubah status aktif akun ini?')"
                                class="w-full py-2.5 px-4 rounded-lg text-xs font-semibold uppercase tracking-wider transition border text-center
                                    {{ $user->is_active 
                                        ? 'bg-red-500/10 border-red-500/30 text-red-400 hover:bg-red-500/20' 
                                        : 'bg-green-500/10 border-green-500/30 text-green-400 hover:bg-green-500/20' }}"
                            >
                                {{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                            </button>
                        </form>

                        <x-atelier.button :href="route('settings.users.index')" variant="ghost" class="w-full text-center">
                            Kembali ke Daftar
                        </x-atelier.button>
                    </div>
                </x-atelier.card>
            </div>

            {{-- Kolom Kanan: Roles & Permissions --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Card Roles --}}
                <x-atelier.card title="Peran Akses (Roles)" subtitle="Daftar peran yang diemban oleh pengguna ini" :brackets="true">
                    <div class="flex flex-wrap gap-2">
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
                            <div class="p-3 rounded-lg border {{ $badgeClass }} flex-1 min-w-[140px]">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-bold">{{ $role->name }}</span>
                                    <span class="text-[10px] font-mono opacity-80">{{ $role->permissions->count() }} izin</span>
                                </div>
                                <span class="text-[11px] opacity-75">Spatie Role</span>
                            </div>
                        @empty
                            <div class="text-sm text-navy-500 italic p-4 bg-navy-950/40 rounded-lg w-full text-center">
                                Pengguna ini belum memiliki role aktif.
                            </div>
                        @endforelse
                    </div>
                </x-atelier.card>

                {{-- Card Hak Akses Efektif (Permissions) --}}
                <x-atelier.card title="Hak Akses Efektif (Permissions)" subtitle="Rincian seluruh izin tindakan yang dimiliki dari kombinasi peran di atas" :brackets="true">
                    @php
                        $userPermissions = $user->getAllPermissions()->sortBy('name');
                    @endphp

                    @if($userPermissions->count() > 0)
                        <div class="space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-navy-800 text-xs">
                                <span class="text-navy-400">Total Izin Aktif:</span>
                                <span class="font-mono text-gold-400 font-bold">{{ $userPermissions->count() }} Permission</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($userPermissions as $perm)
                                    <div class="px-3 py-2 rounded bg-navy-950/70 border border-navy-800 flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-gold-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="text-xs text-navy-200 font-mono truncate" title="{{ $perm->name }}">
                                            {{ $perm->name }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-sm text-navy-500 italic p-4 bg-navy-950/40 rounded-lg text-center">
                            Tidak ada hak akses permission yang aktif untuk pengguna ini.
                        </div>
                    @endif
                </x-atelier.card>

            </div>

        </div>

    </div>
</x-app-layout>
