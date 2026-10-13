<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Detail Work Order</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M5B"
            title="{{ $item->wo_number }}"
            subtitle="{{ $item->product->nama ?? '-' }}"
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

        {{-- INFO --}}
        <x-atelier.card title="Informasi Work Order" :brackets="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">No. WO</p>
                    <p class="text-sm text-gold-500 font-mono">{{ $item->wo_number }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Status</p>
                    <div class="flex gap-2 items-center">
                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full border {{ $item->status_color }}">
                            {{ $item->status_label }}
                        </span>
                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full border {{ $item->priority_color }}">
                            {{ $item->priority_label }}
                        </span>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Produk</p>
                    <p class="text-sm text-white font-medium">{{ $item->product->nama ?? '-' }}</p>
                    <p class="text-xs font-mono text-navy-500">{{ $item->product->kode ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">BOM / Routing</p>
                    <p class="text-xs text-navy-300">BOM: {{ $item->bom->kode ?? '-' }}</p>
                    <p class="text-xs text-navy-300">Routing: {{ $item->routing->kode ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Jadwal</p>
                    <p class="text-sm text-navy-300">
                        {{ format_tanggal($item->start_date) }}
                        @if($item->end_date) — {{ format_tanggal($item->end_date) }} @endif
                    </p>
                    @if($item->actual_start_date)
                        <p class="text-xs text-green-400 mt-1">Actual start: {{ format_tanggal($item->actual_start_date) }}</p>
                    @endif
                    @if($item->actual_end_date)
                        <p class="text-xs text-green-400">Actual end: {{ format_tanggal($item->actual_end_date) }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Dibuat Oleh</p>
                    <p class="text-sm text-navy-300">{{ $item->creator->name ?? '-' }}</p>
                </div>
                @if($item->notes)
                    <div class="md:col-span-2">
                        <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Catatan</p>
                        <p class="text-sm text-navy-300">{{ $item->notes }}</p>
                    </div>
                @endif
            </div>
        </x-atelier.card>

        {{-- PROGRESS BAR --}}
        @php $pct = $item->progress_percent; @endphp
        <x-atelier.card title="Progress Produksi" :brackets="true">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-navy-800/30 border border-navy-700 rounded-lg p-5">
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Rencana</p>
                    <p class="font-serif text-2xl font-bold text-white">{{ format_angka($item->planned_qty, 2) }}</p>
                </div>
                <div class="bg-gradient-to-br from-green-500/10 to-navy-900/50 border border-green-500/30 rounded-lg p-5">
                    <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Selesai</p>
                    <p class="font-serif text-2xl font-bold text-white">{{ format_angka($item->actual_qty, 2) }}</p>
                </div>
                <div class="bg-gradient-to-br from-red-500/10 to-navy-900/50 border border-red-500/30 rounded-lg p-5">
                    <p class="text-[10px] font-mono text-red-400 uppercase tracking-widest mb-1">Reject</p>
                    <p class="font-serif text-2xl font-bold text-white">{{ format_angka($item->rejected_qty, 2) }}</p>
                </div>
            </div>

            <div class="mb-2 flex items-center justify-between">
                <span class="text-xs text-navy-400">Progress</span>
                <span class="text-sm font-mono text-white">{{ number_format($pct, 1) }}%</span>
            </div>
            <div class="w-full bg-navy-800 rounded-full h-4 overflow-hidden">
                <div class="h-full {{ $pct >= 100 ? 'bg-green-500' : 'bg-gold-500' }} transition-all"
                     style="width: {{ $pct }}%"></div>
            </div>
        </x-atelier.card>

        {{-- COST SUMMARY --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-navy-900/50 border border-blue-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-blue-400 uppercase tracking-widest mb-1">Material</p>
                <p class="font-serif text-xl font-bold text-white">{{ format_rupiah($item->total_material_cost) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-gold-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-gold-500 uppercase tracking-widest mb-1">Labor</p>
                <p class="font-serif text-xl font-bold text-white">{{ format_rupiah($item->total_labor_cost) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-orange-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-orange-400 uppercase tracking-widest mb-1">Overhead</p>
                <p class="font-serif text-xl font-bold text-white">{{ format_rupiah($item->total_overhead_cost) }}</p>
            </div>
            <div class="bg-gradient-to-br from-green-500/10 to-navy-900/50 border border-green-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Total / Unit</p>
                <p class="font-serif text-xl font-bold text-white">{{ format_rupiah($item->cost_per_unit) }}</p>
                <p class="text-xs text-navy-500 mt-1">Total: {{ format_rupiah($item->total_cost) }}</p>
            </div>
        </div>

        {{-- MATERIALS --}}
        <x-atelier.card title="Material Requirement" subtitle="Auto-generate dari BOM" :brackets="true" padding="p-0">
            @if($item->materials->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Material</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Dibutuhkan</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Sudah Diambil</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Sisa</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Unit Cost</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Total</th>
                                <th class="px-5 py-3 text-center text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($item->materials as $mat)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm">
                                        <span class="text-white font-medium">{{ $mat->material->nama ?? '-' }}</span>
                                        <br><span class="text-xs font-mono text-navy-500">{{ $mat->material->kode ?? '-' }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-white">
                                        {{ format_angka($mat->required_qty, 4) }}
                                        <span class="text-xs text-navy-500">{{ $mat->unit }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-green-400">
                                        {{ format_angka($mat->issued_qty, 4) }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-gold-500">
                                        {{ format_angka($mat->remaining_qty, 4) }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-navy-300">
                                        {{ format_rupiah($mat->unit_cost) }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-white font-semibold">
                                        {{ format_rupiah($mat->total_cost) }}
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full border {{ $mat->status_color }}">
                                            {{ $mat->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-atelier.empty-state
                    title="Belum ada material requirement"
                    subtitle="Klik 'Release' untuk auto-generate material dari BOM."
                />
            @endif
        </x-atelier.card>

        {{-- PROGRESS STEPS --}}
        <x-atelier.card title="Progress per Step" subtitle="Berdasarkan routing" :brackets="true" padding="p-0">
            @if($item->progress->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Seq</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Operasi</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Work Center</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Qty OK</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Reject</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Cost</th>
                                <th class="px-5 py-3 text-center text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($item->progress as $step)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-navy-500">{{ $step->sequence }}</td>
                                    <td class="px-5 py-3 text-sm text-white font-medium">{{ $step->operation_name }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ $step->workCenter->nama ?? '-' }}</td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-green-400">
                                        {{ format_angka($step->qty_completed, 2) }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-red-400">
                                        {{ format_angka($step->qty_rejected, 2) }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm text-white">
                                        {{ format_rupiah($step->total_cost) }}
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full border {{ $step->status_color }}">
                                            {{ $step->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-atelier.empty-state
                    title="Belum ada progress steps"
                    subtitle="Klik 'Release' untuk auto-generate steps dari Routing."
                />
            @endif
        </x-atelier.card>

        {{-- ACTIONS --}}
        <div class="flex items-center justify-between gap-3">
            <x-atelier.button :href="route('production.work-orders.index')" variant="ghost">Kembali</x-atelier.button>

            <div class="flex gap-3">
                @if($item->canEdit())
                    <x-atelier.button :href="route('production.work-orders.edit', $item)" variant="secondary">Edit</x-atelier.button>

                    <form method="POST" action="{{ route('production.work-orders.recalculate', $item) }}">
                        @csrf
                        <x-atelier.button type="submit" variant="secondary">Hitung Ulang Cost</x-atelier.button>
                    </form>

                    @if($item->canCancel())
                        <form method="POST" action="{{ route('production.work-orders.cancel', $item) }}" onsubmit="return confirm('Batalkan WO ini?')">
                            @csrf
                            @method('PATCH')
                            <x-atelier.button type="submit" variant="danger">Batalkan</x-atelier.button>
                        </form>
                    @endif
                @endif

                @if($item->canRelease())
                    <form method="POST" action="{{ route('production.work-orders.release', $item) }}" onsubmit="return confirm('Release WO ini? Material requirement akan di-generate dari BOM.')">
                        @csrf
                        @method('PATCH')
                        <x-atelier.button type="submit" variant="primary">Release WO</x-atelier.button>
                    </form>
                @endif

                @if($item->canStart())
                    <form method="POST" action="{{ route('production.work-orders.start', $item) }}" onsubmit="return confirm('Mulai produksi?')">
                        @csrf
                        @method('PATCH')
                        <x-atelier.button type="submit" variant="primary">Mulai Produksi</x-atelier.button>
                    </form>
                @endif
                
                @if($item->status === 'in_progress')
                    <x-atelier.button :href="route('production.work-orders.progress', $item)" variant="secondary">
                        📝 Input Progress
                    </x-atelier.button>
                    
                    <x-atelier.button :href="route('production.finished-goods.create', ['work_order_id' => $item->id])" variant="primary">
                        📦 Terima Produk Jadi
                    </x-atelier.button>
                @endif

                @if($item->canComplete())
                    <form method="POST" action="{{ route('production.work-orders.complete', $item) }}" onsubmit="return confirm('Selesaikan WO ini?')">
                        @csrf
                        @method('PATCH')
                        <x-atelier.button type="submit" variant="primary">Selesaikan</x-atelier.button>
                    </form>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>