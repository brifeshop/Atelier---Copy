<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Material</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        {{-- HEADER DENGAN TOMBOL --}}
        <div class="flex items-start justify-between flex-wrap gap-4">
            <x-atelier.page-header
                segment="M2"
                title="Master Material"
                subtitle="Daftar semua bahan baku"
            />
            <div class="flex gap-2 flex-wrap">
                <x-atelier.button :href="route('master.materials.import')" variant="secondary">
                    ⬆️ Import CSV
                </x-atelier.button>
                <x-atelier.button :href="route('master.materials.bulk')" variant="secondary">
                    📋 Input Masal
                </x-atelier.button>
                <x-atelier.button :href="route('master.materials.create')" variant="primary">
                    + Tambah Material
                </x-atelier.button>
            </div>
        </div>

        {{-- ALERT SUCCESS --}}
        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- ALERT ERROR — pakai {!! !!} supaya HTML render --}}
        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">
                {!! session('error') !!}
            </div>
        @endif

        {{-- CARD DENGAN FILTER & TABEL --}}
        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('master.materials.index') }}" class="flex gap-3 flex-wrap">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari kode, kode internal, nama, spesifikasi..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                    >
                    <select name="costing_method" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                        <option value="">Semua Costing Method</option>
                        <option value="per_unit" @selected(request('costing_method') == 'per_unit')>Per Unit</option>
                        <option value="per_area" @selected(request('costing_method') == 'per_area')>Per Area</option>
                        <option value="per_volume" @selected(request('costing_method') == 'per_volume')>Per Volume</option>
                        <option value="per_length" @selected(request('costing_method') == 'per_length')>Per Panjang</option>
                        <option value="per_weight" @selected(request('costing_method') == 'per_weight')>Per Berat</option>
                    </select>
                    <x-atelier.button type="submit" variant="secondary">Cari</x-atelier.button>
                    @if(request()->hasAny(['search', 'costing_method']))
                        <x-atelier.button :href="route('master.materials.index')" variant="ghost">Reset</x-atelier.button>
                    @endif
                </form>
            </div>

            @if($items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest w-16">Foto</th>
                                <x-sortable-header column="kode" label="Kode" align="left" />
                                <x-sortable-header column="nama" label="Nama" align="left" />
                                <th class="px-4 py-3 text-center text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Costing</th>
                                <x-sortable-header column="category" label="Kategori" align="left" />
                                <x-sortable-header column="price" label="Harga" align="right" />
                                <x-sortable-header column="current_stock" label="Stok" align="right" />
                                <th class="px-4 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Status</th>
                                <th class="px-4 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($items as $material)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-4 py-3">
                                        @if($material->photo_url)
                                            <img src="{{ $material->photo_url }}"
                                                 alt="{{ $material->nama }}"
                                                 class="w-12 h-12 object-cover rounded-lg border border-navy-700">
                                        @else
                                            <div class="w-12 h-12 rounded-lg border border-navy-700 bg-navy-900 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-navy-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="font-mono text-gold-500">{{ $material->kode }}</span>
                                        @if($material->kode_bahan)
                                            <br><span class="text-xs text-navy-400 font-mono">{{ $material->kode_bahan }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="text-white font-medium">{{ $material->nama }}</span>
                                        @if($material->spesifikasi)
                                            <br><span class="text-xs text-navy-500">{{ Str::limit($material->spesifikasi, 60) }}</span>
                                        @endif
                                        @if($material->location)
                                            <br><span class="text-xs text-navy-500">📍 {{ $material->location }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $methodShort = match($material->costing_method) {
                                                'per_unit'   => 'Unit',
                                                'per_area'   => 'Area',
                                                'per_volume' => 'Volume',
                                                'per_length' => 'Panjang',
                                                'per_weight' => 'Berat',
                                                default      => 'Unit',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center text-[10px] px-2 py-0.5 rounded-full border {{ $material->costing_method_color }}">
                                            {{ $methodShort }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-navy-300">{{ $material->category ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-mono text-white">
                                        {{ format_rupiah($material->price) }}
                                        <br><span class="text-xs text-navy-500">/{{ $material->unit }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @php
                                            $isLow = ($material->min_stock ?? 0) > 0 && $material->current_stock <= $material->min_stock;
                                        @endphp
                                        <span class="text-sm font-mono {{ $isLow ? 'text-red-400' : 'text-white' }}">
                                            {{ format_angka($material->current_stock, 1) }}
                                            <span class="text-xs text-navy-500">{{ $material->unit }}</span>
                                        </span>
                                        @if($isLow)
                                            <br><span class="text-[10px] text-red-400 font-mono">⚠ LOW</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($material->is_active)
                                            <span class="inline-flex items-center gap-1.5 text-xs text-green-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-xs text-navy-500">
                                                <span class="w-1.5 h-1.5 rounded-full bg-navy-500"></span>Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-atelier.button :href="route('master.materials.show', $material)" variant="ghost" size="sm">Lihat</x-atelier.button>
                                            <x-atelier.button :href="route('master.materials.edit', $material)" variant="secondary" size="sm">Edit</x-atelier.button>

                                            @php
                                                $isUsedInBom = \App\Models\Engineering\BomItem::where('item_type', 'material')->where('item_id', $material->id)->exists();
                                                $isUsedInWo = \App\Models\Production\WorkOrderMaterial::where('material_id', $material->id)->exists();
                                                $canDelete = !$isUsedInBom && !$isUsedInWo;
                                            @endphp

                                            @if($canDelete)
                                                <form method="POST" action="{{ route('master.materials.destroy', $material) }}" onsubmit="return confirm('Yakin hapus material ini?')" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-atelier.button type="submit" variant="danger" size="sm">Hapus</x-atelier.button>
                                                </form>
                                            @else
                                                <button type="button"
                                                        disabled
                                                        title="Material masih dipakai di BOM atau Work Order. Nonaktifkan saja untuk menyembunyikannya."
                                                        class="px-3 py-1.5 text-xs font-semibold text-navy-500 bg-navy-800 border border-navy-700 rounded-lg cursor-not-allowed">
                                                    Dipakai
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($items->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">
                        {{ $items->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <x-atelier.empty-state
                    title="Belum ada material"
                    subtitle="Mulai dengan menambahkan material pertama."
                    action="Tambah Material"
                    :actionUrl="route('master.materials.create')"
                />
            @endif
        </x-atelier.card>

    </div>
</x-app-layout>