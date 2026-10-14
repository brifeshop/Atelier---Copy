<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Edit User: {{ $user->name }}</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="SETTINGS"
            title="Edit User"
            subtitle="Perbarui informasi profil dan peran akses pengguna"
        />

        {{-- Flash Messages --}}
        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('settings.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Kolom Kiri: Informasi Akun --}}
                <div class="lg:col-span-1 space-y-6">
                    <x-atelier.card title="Informasi Akun" subtitle="Identitas akun pengguna" :brackets="true">
                        <div class="space-y-4">
                            <x-atelier.input
                                name="name"
                                label="Nama Lengkap"
                                :value="$user->name"
                                :required="true"
                            />

                            <x-atelier.input
                                name="email"
                                label="Alamat Email"
                                type="email"
                                :value="$user->email"
                                :required="true"
                            />

                            <div class="pt-2">
                                <x-atelier.input
                                    name="password"
                                    label="Password Baru"
                                    type="password"
                                    placeholder="••••••••"
                                    hint="Kosongkan jika tidak ingin mengubah password"
                                />
                            </div>

                            <x-atelier.input
                                name="password_confirmation"
                                label="Konfirmasi Password Baru"
                                type="password"
                                placeholder="••••••••"
                            />

                            <div class="pt-3 border-t border-navy-800">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="hidden" name="is_active" value="0">
                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        @checked(old('is_active', $user->is_active ? '1' : '0') == '1')
                                        class="rounded border-navy-700 bg-navy-950 text-gold-500 focus:ring-gold-500/30 w-4 h-4"
                                    >
                                    <div>
                                        <span class="text-sm font-medium text-white">Akun Aktif</span>
                                        <p class="text-xs text-navy-400">Izinkan pengguna ini login ke sistem</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </x-atelier.card>
                </div>

                {{-- Kolom Kanan: Pilihan Role --}}
                <div class="lg:col-span-2 space-y-6">
                    <x-atelier.card title="Penetapan Role" subtitle="Pilih peran yang diberikan kepada pengguna ini" :brackets="true">
                        
                        @error('roles')
                            <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-xs text-red-400">
                                {{ $message }}
                            </div>
                        @enderror

                        @php
                            $roleDescriptions = [
                                'Owner'      => 'Akses eksekutif tertinggi ke seluruh modul bisnis, finansial, dan audit.',
                                'Admin'      => 'Pengelolaan sistem penuh, manajemen pengguna, konfigurasi dan operasional ERP.',
                                'Manager'    => 'Persetujuan transaksi, pemantauan lintas divisi, dan analisis laporan eksekutif.',
                                'Sales'      => 'Pengelolaan data Customer dan penerbitan formulir Sales Order (SO).',
                                'PPIC'       => 'Perencanaan kebutuhan material, Master BOM, Routing, dan penjadwalan Work Order.',
                                'Purchasing' => 'Pengadaan bahan baku, Purchase Requisition, PO, dan evaluasi vendor.',
                                'Warehouse'  => 'Manajemen lokasi rak gudang, penerimaan barang, mutasi stok, dan Stock Opname.',
                                'Produksi'   => 'Pelaksanaan lantai pabrik, penarikan material (Material Issue), dan progres WO.',
                                'QC'         => 'Pemeriksaan standar kualitas material masuk dan barang jadi pasca-produksi.',
                                'Finance'    => 'Pencatatan faktur (invoicing), verifikasi kas/pembayaran, dan laporan keuangan.',
                            ];
                        @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            @foreach($roles as $role)
                                @php
                                    $isOwner = $role->name === 'Owner';
                                    $canAssignOwner = auth()->user()->hasRole('Owner');
                                    $disabled = $isOwner && !$canAssignOwner;
                                @endphp
                                <label class="relative flex items-start gap-3 p-3.5 rounded-lg border transition cursor-pointer select-none
                                    {{ $disabled ? 'opacity-50 cursor-not-allowed bg-navy-950/40 border-navy-800' : 'bg-navy-950/60 border-navy-800 hover:border-gold-500/40 hover:bg-navy-900/60' }}
                                    has-[:checked]:border-gold-500 has-[:checked]:bg-gold-500/5">
                                    <input
                                        type="checkbox"
                                        name="roles[]"
                                        value="{{ $role->name }}"
                                        @checked(in_array($role->name, old('roles', $userRoles)))
                                        @disabled($disabled)
                                        class="mt-1 rounded border-navy-700 bg-navy-900 text-gold-500 focus:ring-gold-500/30 w-4 h-4"
                                    >
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-white">{{ $role->name }}</span>
                                            @if($isOwner)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] bg-gold-500/20 text-gold-400 border border-gold-500/40 font-mono">Super</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-navy-400 mt-1 leading-relaxed">
                                            {{ $roleDescriptions[$role->name] ?? 'Akses operasional modul ' . $role->name }}
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-navy-800">
                            <x-atelier.button :href="route('settings.users.index')" variant="ghost">Batal</x-atelier.button>
                            <x-atelier.button type="submit" variant="primary">Simpan Perubahan</x-atelier.button>
                        </div>
                    </x-atelier.card>
                </div>

            </div>
        </form>

    </div>
</x-app-layout>
