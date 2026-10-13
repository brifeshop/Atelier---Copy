<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Detail FG Receipt</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M5B"
            title="{{ $item->fgr_number }}"
            subtitle="{{ $item->workOrder->wo_number ?? '-' }} — {{ format_tanggal($item->date) }}"
        />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">
                {!! session('error') !!}
            </div>
        @endif

        {{-- INFO --}}
        <x-atelier.card title="Informasi FG Receipt" :brackets="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">No. FGR</p>
                    <p class="text-sm text-gold-500 font-mono">{{ $item->fgr_number }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Status</p>
                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full border {{ $item->status_color }}">
                        {{ $item->status_label }}
                    </span>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Tanggal</p>
                    <p class="text-sm text-navy-300">{{ format_tanggal($item->date) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Gudang</p>
                    <p class="text-sm text-navy-300">{{ $item->warehouse->nama ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Work Order</p>
                    <a href="{{ route('production.work-orders.show', $item->work_order_id) }}" 
                       class="text-sm text-gold-500 hover:text-gold-400 font-mono">
                        {{ $item->workOrder->wo_number ?? '-' }}
                    </a>
                    <p class="text-xs text-navy-500 mt-0.5">{{ $item->workOrder->product->nama ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Diterima Oleh</p>
                    <p class="text-sm text-navy-300">{{ $item->receiver->name ?? '-' }}</p>
                    @if($item->received_at)
                        <p class="text-xs text-navy-500">{{ $item->received_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
                @if($item->notes)
                    <div class="md:col-span-2">
                        <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Catatan</p>
                        <p class="text-sm text-navy-300">{{ $item->notes }}</p>
                    </div>
                @endif
            </div>
        </x-atelier.card>

        {{-- QTY SUMMARY --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gradient-to-br from-green-500/10 to-navy-900/50 border border-green-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Qty Good</p>
                <p class="font-serif text-3xl font-bold text-white">{{ format_angka($item->qty_good, 2) }}</p>
            </div>
            <div class="bg-gradient-to-br from-red-500/10 to-navy-900/50 border border-red-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-red-400 uppercase tracking-widest mb-1">Qty Reject</p>
                <p class="font-serif text-3xl font-bold text-white">{{ format_angka($item->qty_rejected, 2) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-gold-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-gold-500 uppercase tracking-widest mb-1">Total Value</p>
                <p class="font-serif text-2xl font-bold text-white">{{ format_rupiah($item->total_value) }}</p>
                <p class="text-xs text-navy-400 mt-1">@ {{ format_rupiah($item->unit_cost) }}/unit</p>
            </div>
        </div>

        {{-- ITEMS (Distribusi Lokasi) --}}
        <x-atelier.card title="Distribusi ke Lokasi" :brackets="true" padding="p-0">
            @if($item->items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Lokasi</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Qty</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Unit Cost</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Total</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($item->items as $fgrItem)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm">
                                        <span class="text-white font-medium">{{ $fgrItem->location->nama ?? '-' }}</span>
                                        <br><span class="text-xs font-mono text-navy-500">{{ $fgrItem->location->kode ?? '-' }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-white">
                                        {{ format_angka($fgrItem->qty, 2) }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-navy-300">
                                        {{ format_rupiah($fgrItem->unit_cost) }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-white font-semibold">
                                        {{ format_rupiah($fgrItem->total_value) }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-navy-400">
                                        {{ $fgrItem->notes ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gold-500/10 border-t-2 border-gold-500/30">
                            <tr>
                                <td class="px-5 py-3 text-right text-sm text-gold-500 font-semibold uppercase tracking-wider">
                                    Total
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-base text-white font-bold">
                                    {{ format_angka($item->items->sum('qty'), 2) }}
                                </td>
                                <td class="px-5 py-3"></td>
                                <td class="px-5 py-3 text-right font-mono text-base text-white font-bold">
                                    {{ format_rupiah($item->items->sum('total_value')) }}
                                </td>
                                <td class="px-5 py-3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <x-atelier.empty-state
                    title="Belum ada item"
                    subtitle="FG Receipt ini tidak punya item distribusi."
                />
            @endif
        </x-atelier.card>

        {{-- ACTIONS --}}
        <div class="flex items-center justify-between gap-3">
            <x-atelier.button :href="route('production.finished-goods.index')" variant="ghost">Kembali</x-atelier.button>

            <div class="flex gap-3">
                @if($item->status === 'draft')
                    <form method="POST" action="{{ route('production.finished-goods.destroy', $item) }}" 
                          onsubmit="return confirm('Hapus FG Receipt ini?')">
                        @csrf
                        @method('DELETE')
                        <x-atelier.button type="submit" variant="danger">Hapus</x-atelier.button>
                    </form>

                    <form method="POST" action="{{ route('production.finished-goods.receive', $item) }}"
                          onsubmit="return confirm('Terima produk ini? Stok akan ditambahkan.')">
                        @csrf
                        @method('PATCH')
                        <x-atelier.button type="submit" variant="primary">
                            ✓ Terima Produk ({{ format_angka($item->qty_good, 0) }} unit)
                        </x-atelier.button>
                    </form>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>