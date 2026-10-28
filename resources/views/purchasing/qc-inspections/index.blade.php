<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">QC Incoming</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="QC"
            title="QC Incoming"
            subtitle="Inspeksi kualitas bahan baku dari supplier"
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

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-navy-900/50 border border-yellow-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-yellow-400 uppercase tracking-widest mb-1">Pending</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['pending'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-green-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Completed</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['completed'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-red-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-red-400 uppercase tracking-widest mb-1">Rejected</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['rejected'] }}</p>
            </div>
        </div>

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('purchasing.qc-inspections.index') }}" class="flex gap-3 flex-wrap">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. QC / GR / Supplier..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                    <select name="status" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                        <option value="">Semua Status</option>
                        <option value="pending" @selected(request('status') == 'pending')>Pending</option>
                        <option value="completed" @selected(request('status') == 'completed')>Completed</option>
                        <option value="rejected" @selected(request('status') == 'rejected')>Rejected</option>
                    </select>
                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                </form>
            </div>

            @if($inspections->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">No. QC</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">No. GR</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Supplier</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Tanggal</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Inspector</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Status</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($inspections as $qc)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $qc->qc_number }}</td>
                                    <td class="px-5 py-3 text-sm font-mono text-navy-300">{{ $qc->goodsReceipt->gr_number ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-white">{{ $qc->goodsReceipt->supplier->nama ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ format_tanggal($qc->inspection_date) }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ $qc->inspector->name ?? '-' }}</td>
                                    <td class="px-5 py-3">
                                        @php
                                            $colors = [
                                                'pending'   => 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
                                                'completed' => 'bg-green-500/10 border-green-500/30 text-green-400',
                                                'rejected'  => 'bg-red-500/10 border-red-500/30 text-red-400',
                                            ];
                                        @endphp
                                        <span class="text-xs px-2 py-1 rounded-full border {{ $colors[$qc->status] ?? '' }}">
                                            {{ ucfirst($qc->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <x-atelier.button :href="route('purchasing.qc-inspections.show', $qc)" variant="ghost" size="sm">Detail</x-atelier.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 border-t border-navy-800">{{ $inspections->links() }}</div>
            @else
                <x-atelier.empty-state title="Belum ada QC" subtitle="QC otomatis dibuat saat Goods Receipt diterima." />
            @endif
        </x-atelier.card>
    </div>
</x-app-layout>