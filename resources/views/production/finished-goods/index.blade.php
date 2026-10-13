<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Finished Goods Receipt</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M5B"
            title="FG Receipt"
            subtitle="Penerimaan produk jadi dari produksi"
            action="Buat FG Receipt"
            :actionUrl="route('production.finished-goods.create')"
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

        {{-- STATS --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Total FGR</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Draft</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['draft'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-green-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Diterima</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['received'] }}</p>
            </div>
        </div>

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('production.finished-goods.index') }}" class="flex gap-3 flex-wrap">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nomor FGR, WO, produk..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                    >
                    <select name="status" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                        <option value="">Semua Status</option>
                        <option value="draft" @selected(request('status') == 'draft')>Draft</option>
                        <option value="received" @selected(request('status') == 'received')>Diterima</option>
                    </select>
                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                    @if(request()->hasAny(['search', 'status']))
                        <x-atelier.button :href="route('production.finished-goods.index')" variant="ghost">Reset</x-atelier.button>
                    @endif
                </form>
            </div>

            @if($items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">No. FGR</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Tanggal</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">No. WO</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Produk</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Gudang</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Qty Good</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Qty Reject</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Total Value</th>
                                <th class="px-5 py-3 text-center text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Status</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($items as $fgr)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $fgr->fgr_number }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ format_tanggal($fgr->date) }}</td>
                                    <td class="px-5 py-3 text-sm font-mono text-navy-300">{{ $fgr->workOrder->wo_number ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-white">
                                        {{ $fgr->product->nama ?? '-' }}
                                        <br><span class="text-xs font-mono text-navy-500">{{ $fgr->product->kode ?? '-' }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ $fgr->warehouse->nama ?? '-' }}</td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-green-400">
                                        {{ format_angka($fgr->qty_good, 2) }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-red-400">
                                        {{ format_angka($fgr->qty_rejected, 2) }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-white font-semibold">
                                        {{ format_rupiah($fgr->total_value) }}
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full border {{ $fgr->status_color }}">
                                            {{ $fgr->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <x-atelier.button :href="route('production.finished-goods.show', $fgr)" variant="ghost" size="sm">Detail</x-atelier.button>
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
                    title="Belum ada FG Receipt"
                    subtitle="Buat FG Receipt dari Work Order yang sedang produksi."
                    action="Buat FG Receipt"
                    :actionUrl="route('production.finished-goods.create')"
                />
            @endif
        </x-atelier.card>

    </div>
</x-app-layout>