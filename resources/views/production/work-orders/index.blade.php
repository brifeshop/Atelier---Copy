<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Work Order</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M5B"
            title="Work Order"
            subtitle="Perintah produksi & tracking"
            action="Buat Work Order"
            :actionUrl="route('production.work-orders.create')"
        />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- STATS --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Total WO</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Draft</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['draft'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-blue-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-blue-400 uppercase tracking-widest mb-1">Released</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['released'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-gold-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-gold-500 uppercase tracking-widest mb-1">In Progress</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['in_progress'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-green-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Selesai</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['completed'] }}</p>
            </div>
        </div>

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('production.work-orders.index') }}" class="flex gap-3 flex-wrap">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nomor WO, produk..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                    >
                    <select name="product_id" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                        <option value="">Semua Produk</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>
                                {{ $p->kode }} — {{ $p->nama }}
                            </option>
                        @endforeach
                    </select>
                    <select name="status" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                        <option value="">Semua Status</option>
                        <option value="draft" @selected(request('status') == 'draft')>Draft</option>
                        <option value="released" @selected(request('status') == 'released')>Released</option>
                        <option value="in_progress" @selected(request('status') == 'in_progress')>In Progress</option>
                        <option value="completed" @selected(request('status') == 'completed')>Selesai</option>
                        <option value="cancelled" @selected(request('status') == 'cancelled')>Dibatalkan</option>
                    </select>
                    <select name="priority" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                        <option value="">Semua Prioritas</option>
                        <option value="low" @selected(request('priority') == 'low')>Rendah</option>
                        <option value="normal" @selected(request('priority') == 'normal')>Normal</option>
                        <option value="high" @selected(request('priority') == 'high')>Tinggi</option>
                        <option value="urgent" @selected(request('priority') == 'urgent')>Urgent</option>
                    </select>
                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                    @if(request()->hasAny(['search', 'product_id', 'status', 'priority']))
                        <x-atelier.button :href="route('production.work-orders.index')" variant="ghost">Reset</x-atelier.button>
                    @endif
                </form>
            </div>

            @if($items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <x-sortable-header column="wo_number" label="No. WO" align="left" />
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Produk</th>
                                <x-sortable-header column="planned_qty" label="Qty" align="right" />
                                <x-sortable-header column="start_date" label="Jadwal" align="left" />
                                <x-sortable-header column="priority" label="Prioritas" align="center" />
                                <x-sortable-header column="status" label="Status" align="center" />
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Progress</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($items as $wo)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $wo->wo_number }}</td>
                                    <td class="px-5 py-3 text-sm">
                                        <span class="text-white font-medium">{{ $wo->product->nama ?? '-' }}</span>
                                        <br><span class="text-xs font-mono text-navy-500">{{ $wo->product->kode ?? '-' }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-white">
                                        {{ format_angka($wo->planned_qty, 2) }}
                                        @if($wo->actual_qty > 0)
                                            <br><span class="text-xs text-green-400">Done: {{ format_angka($wo->actual_qty, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-sm text-navy-300">
                                        {{ format_tanggal($wo->start_date) }}
                                        @if($wo->end_date)
                                            <br><span class="text-xs text-navy-500">s/d {{ format_tanggal($wo->end_date) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full border {{ $wo->priority_color }}">
                                            {{ $wo->priority_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full border {{ $wo->status_color }}">
                                            {{ $wo->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 min-w-[120px]">
                                        @php $pct = $wo->progress_percent; @endphp
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-navy-800 rounded-full h-2 overflow-hidden">
                                                <div class="h-full {{ $pct >= 100 ? 'bg-green-500' : ($pct > 0 ? 'bg-gold-500' : 'bg-navy-600') }}"
                                                     style="width: {{ $pct }}%"></div>
                                            </div>
                                            <span class="text-xs font-mono text-navy-400 w-10 text-right">{{ number_format($pct, 0) }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <x-atelier.button :href="route('production.work-orders.show', $wo)" variant="ghost" size="sm">Detail</x-atelier.button>
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
                    title="Belum ada Work Order"
                    subtitle="Mulai dengan membuat WO pertama."
                    action="Buat Work Order"
                    :actionUrl="route('production.work-orders.create')"
                />
            @endif
        </x-atelier.card>

    </div>
</x-app-layout>