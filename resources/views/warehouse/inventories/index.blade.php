<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Inventory</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M4A"
            title="Inventory"
            subtitle="Stok barang di semua lokasi"
        />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- STATS --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Item Tersedia</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['total_items'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Lokasi Aktif</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['total_locations'] }}</p>
            </div>
            <div class="bg-navy-900/50 border {{ $stats['low_stock_count'] > 0 ? 'border-red-500/50' : 'border-navy-800' }} rounded-xl p-5">
                <p class="text-[10px] font-mono {{ $stats['low_stock_count'] > 0 ? 'text-red-400' : 'text-navy-400' }} uppercase tracking-widest mb-1">Stok Rendah</p>
                <p class="font-serif text-3xl font-bold {{ $stats['low_stock_count'] > 0 ? 'text-red-400' : 'text-white' }}">{{ $stats['low_stock_count'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Total Value</p>
                <p class="font-serif text-2xl font-bold text-white">{{ format_rupiah($stats['total_value']) }}</p>
            </div>
        </div>

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('warehouse.inventories.index') }}" class="flex gap-3 flex-wrap">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari kode atau nama item..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                    >
                    <select name="warehouse_id" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                        <option value="">Semua Warehouse</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>
                                {{ $wh->nama }}
                            </option>
                        @endforeach
                    </select>
                    <select name="item_type" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                        <option value="">Semua Type</option>
                        <option value="material" @selected(request('item_type') == 'material')>Material</option>
                        <option value="product" @selected(request('item_type') == 'product')>Product</option>
                    </select>
                    <label class="flex items-center gap-2 px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white cursor-pointer hover:border-gold-500/50">
                        <input type="checkbox" name="low_stock" value="1" @checked(request('low_stock'))
                               class="rounded border-navy-600 bg-navy-900 text-gold-500 focus:ring-gold-500">
                        <span>Stok Rendah</span>
                    </label>
                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                    @if(request()->hasAny(['search', 'warehouse_id', 'item_type', 'low_stock']))
                        <x-atelier.button :href="route('warehouse.inventories.index')" variant="ghost">Reset</x-atelier.button>
                    @endif
                </form>
            </div>

            @if($items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Item</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Type</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Lokasi</th>
                                <x-sortable-header column="qty" label="Qty" align="right" />
                                <x-sortable-header column="min_stock" label="Min / Max" align="right" />
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Status</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Value</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($items as $inv)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm">
                                        @if($inv->item)
                                            <span class="text-white font-medium">{{ $inv->item->nama }}</span>
                                            <br><span class="text-xs font-mono text-gold-500">{{ $inv->item->kode }}</span>
                                        @else
                                            <span class="text-navy-500 italic">Item tidak ditemukan</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        @php
                                            $typeColor = $inv->item_type === 'material'
                                                ? 'bg-blue-500/10 text-blue-400 border-blue-500/30'
                                                : 'bg-green-500/10 text-green-400 border-green-500/30';
                                        @endphp
                                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full border {{ $typeColor }}">
                                            {{ ucfirst($inv->item_type) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-navy-300">
                                        {{ $inv->location->nama ?? '-' }}
                                        <br><span class="text-xs text-navy-500">{{ $inv->location->warehouse->nama ?? '-' }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="text-sm font-mono {{ $inv->is_low_stock ? 'text-red-400' : 'text-white' }}">
                                            {{ format_angka($inv->qty, 2) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs font-mono text-navy-400">
                                        {{ $inv->min_stock ? format_angka($inv->min_stock, 0) : '-' }}
                                        /
                                        {{ $inv->max_stock ? format_angka($inv->max_stock, 0) : '-' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($inv->is_low_stock)
                                            <span class="inline-flex items-center gap-1.5 text-xs text-red-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>Low Stock
                                            </span>
                                        @elseif($inv->qty > 0)
                                            <span class="inline-flex items-center gap-1.5 text-xs text-green-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>Tersedia
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-xs text-navy-500">
                                                <span class="w-1.5 h-1.5 rounded-full bg-navy-500"></span>Kosong
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-gold-500">
                                        {{ format_rupiah($inv->total_value) }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <x-atelier.button :href="route('warehouse.inventories.show', $inv)" variant="ghost" size="sm">Detail</x-atelier.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($items->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">
                        {{ $items->links() }}
                    </div>
                @endif
            @else
                <x-atelier.empty-state
                    title="Belum ada inventory"
                    subtitle="Inventory akan muncul otomatis saat ada barang masuk dari Goods Receipt atau Material Issue."
                />
            @endif
        </x-atelier.card>

    </div>
</x-app-layout>