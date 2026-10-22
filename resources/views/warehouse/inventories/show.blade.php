<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Detail Inventory</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M4A"
            title="{{ $inventory->item->nama ?? 'Item' }}"
            subtitle="Kode: {{ $inventory->item->kode ?? '-' }}"
        />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-atelier.card title="Informasi Item" :brackets="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Item</p>
                    <p class="text-sm text-white font-medium">{{ $inventory->item->nama ?? '-' }}</p>
                    <p class="text-xs font-mono text-gold-500">{{ $inventory->item->kode ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Type</p>
                    <p class="text-sm text-navy-300">{{ ucfirst($inventory->item_type) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Lokasi</p>
                    <p class="text-sm text-white">{{ $inventory->location->nama ?? '-' }}</p>
                    <p class="text-xs text-navy-500">{{ $inventory->location->warehouse->nama ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Last Movement</p>
                    <p class="text-sm text-navy-300">{{ $inventory->last_movement_at ? format_tanggal_waktu($inventory->last_movement_at) : '-' }}</p>
                </div>
            </div>
        </x-atelier.card>

        {{-- STOK --}}
        <x-atelier.card title="Stok" :brackets="true">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-br {{ $inventory->is_low_stock ? 'from-red-500/10 border-red-500/30' : 'from-gold-500/10 border-gold-500/30' }} to-navy-900/50 border rounded-lg p-5">
                    <p class="text-[10px] font-mono {{ $inventory->is_low_stock ? 'text-red-400' : 'text-gold-500' }} uppercase tracking-widest mb-1">Stok Saat Ini</p>
                    <p class="font-serif text-3xl font-bold text-white">
                        {{ format_angka($inventory->qty, 2) }}
                    </p>
                    @if($inventory->is_low_stock)
                        <p class="text-xs text-red-400 mt-1">⚠ Di bawah minimum!</p>
                    @endif
                </div>
                <div class="bg-navy-800/30 border border-navy-700 rounded-lg p-5">
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Stok Minimum</p>
                    <p class="font-serif text-3xl font-bold text-white">
                        {{ $inventory->min_stock ? format_angka($inventory->min_stock, 2) : '-' }}
                    </p>
                </div>
                <div class="bg-navy-800/30 border border-navy-700 rounded-lg p-5">
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Stok Maksimum</p>
                    <p class="font-serif text-3xl font-bold text-white">
                        {{ $inventory->max_stock ? format_angka($inventory->max_stock, 2) : '-' }}
                    </p>
                </div>
            </div>
        </x-atelier.card>

        {{-- FORM UPDATE MIN/MAX --}}
        <x-atelier.card title="Setting Min/Max Stock" subtitle="Atur batas stok minimum dan maksimum" :brackets="true">
            <form method="POST" action="{{ route('warehouse.inventories.update', $inventory) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-atelier.input name="min_stock" label="Stok Minimum" type="number" step="0.01" :value="$inventory->min_stock" hint="Alert kalau stok ≤ ini" />
                    <x-atelier.input name="max_stock" label="Stok Maksimum" type="number" step="0.01" :value="$inventory->max_stock" />
                </div>

                <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-navy-800">
                    <x-atelier.button type="submit" variant="primary">Simpan Setting</x-atelier.button>
                </div>
            </form>
        </x-atelier.card>

        {{-- Placeholder Stock Movement --}}
        <x-atelier.card title="Riwayat Pergerakan" :brackets="true" padding="p-0">
            @if($movements->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Tanggal</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">No. Mutasi</th>
                                <th class="px-5 py-3 text-center text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Tipe</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Qty</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Referensi</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Catatan</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($movements as $mv)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm text-navy-300">
                                        {{ format_tanggal($mv->date) }}
                                    </td>
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">
                                        {{ $mv->movement_number }}
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        @php
                                            $typeColors = [
                                                'in' => 'text-green-400 bg-green-500/10',
                                                'out' => 'text-red-400 bg-red-500/10',
                                                'transfer' => 'text-blue-400 bg-blue-500/10',
                                                'adjustment' => 'text-yellow-400 bg-yellow-500/10',
                                            ];
                                            $color = $typeColors[$mv->type] ?? 'text-navy-300 bg-navy-800';
                                        @endphp
                                        <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $color }}">
                                            {{ $mv->type }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-right font-mono {{ $mv->type === 'in' ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $mv->type === 'in' ? '+' : '-' }}{{ number_format(abs($mv->qty), 2) }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-navy-300">
                                        {{ $mv->reference_number ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-navy-400">
                                        {{ $mv->notes ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-navy-500">
                                        {{ $mv->createdBy->name ?? 'System' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($movements->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">
                        {{ $movements->links() }}
                    </div>
                @endif
            @else
                <div class="p-8 text-center">
                    <p class="text-sm text-navy-400">Belum ada riwayat pergerakan stok untuk item ini.</p>
                </div>
            @endif
        </x-atelier.card>

        <div class="flex items-center justify-between gap-3">
            <x-atelier.button :href="route('warehouse.inventories.index')" variant="ghost">Kembali</x-atelier.button>
        </div>

    </div>
</x-app-layout>