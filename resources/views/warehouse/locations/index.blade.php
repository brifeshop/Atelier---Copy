<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Location</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <div class="flex items-start justify-between flex-wrap gap-4">
        <x-atelier.page-header
            segment="M4A"
            title="Location"
            subtitle="Daftar rak / lokasi penyimpanan"
        />
        <div class="flex gap-2">
            <x-atelier.button :href="route('warehouse.locations.bulk')" variant="secondary">
                📋 Input Masal
            </x-atelier.button>
            <x-atelier.button :href="route('warehouse.locations.create')" variant="primary">
                + Tambah Location
            </x-atelier.button>
        </div>
    </div>

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('warehouse.locations.index') }}" class="flex gap-3 flex-wrap">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari kode, nama..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                    >
                    <select name="warehouse_id" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                        <option value="">Semua Warehouse</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>
                                {{ $wh->kode }} — {{ $wh->nama }}
                            </option>
                        @endforeach
                    </select>
                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                    @if(request('search') || request('warehouse_id'))
                        <x-atelier.button :href="route('warehouse.locations.index')" variant="ghost">Reset</x-atelier.button>
                    @endif
                </form>
            </div>

            @if($items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <x-sortable-header column="kode" label="Kode" align="left" />
                                <x-sortable-header column="nama" label="Nama" align="left" />
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Warehouse</th>
                                <x-sortable-header column="capacity" label="Capacity" align="right" />
                                <x-sortable-header column="is_active" label="Status" align="left" />
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($items as $location)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $location->kode }}</td>
                                    <td class="px-5 py-3 text-sm text-white font-medium">{{ $location->nama }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">
                                        {{ $location->warehouse->nama ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-navy-300">
                                        {{ $location->capacity ? format_angka($location->capacity, 2) : '-' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($location->is_active)
                                            <span class="inline-flex items-center gap-1.5 text-xs text-green-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-xs text-navy-500">
                                                <span class="w-1.5 h-1.5 rounded-full bg-navy-500"></span>Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-atelier.button :href="route('warehouse.locations.show', $location)" variant="ghost" size="sm">Lihat</x-atelier.button>
                                            <x-atelier.button :href="route('warehouse.locations.edit', $location)" variant="secondary" size="sm">Edit</x-atelier.button>
                                            <form method="POST" action="{{ route('warehouse.locations.destroy', $location) }}" onsubmit="return confirm('Yakin hapus location ini?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <x-atelier.button type="submit" variant="danger" size="sm">Hapus</x-atelier.button>
                                            </form>
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
                    title="Belum ada location"
                    subtitle="Mulai dengan menambahkan location pertama."
                    action="Tambah Location"
                    :actionUrl="route('warehouse.locations.create')"
                />
            @endif
        </x-atelier.card>

    </div>
</x-app-layout>