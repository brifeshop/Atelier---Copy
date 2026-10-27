<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Laporan Umur Hutang (AP Aging)</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="REPORT"
            title="Laporan Umur Hutang (AP Aging)"
            subtitle="Analisis umur hutang supplier"
        />

        {{-- Filter --}}
        <x-atelier.card :brackets="true">
            <form method="GET" action="{{ route('finance.reports.aging-ap') }}" class="flex gap-3 flex-wrap items-end">
                <div>
                    <label class="text-[10px] font-mono text-navy-400 uppercase tracking-widest block mb-1">Per Tanggal</label>
                    <input type="date" name="as_of_date" value="{{ $asOfDate }}"
                        class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                </div>
                <x-atelier.button type="submit" variant="primary">Tampilkan</x-atelier.button>
                <a href="{{ route('finance.reports.aging-ap') }}" class="text-sm text-navy-400 hover:text-gold-500 px-3 py-2">Reset</a>
            </form>
        </x-atelier.card>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-navy-900/50 border border-green-500/30 rounded-xl p-4">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Belum Jatuh Tempo</p>
                <p class="font-mono text-lg font-bold text-white">{{ format_rupiah($grandTotal['current']) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-blue-500/30 rounded-xl p-4">
                <p class="text-[10px] font-mono text-blue-400 uppercase tracking-widest mb-1">1 - 30 Hari</p>
                <p class="font-mono text-lg font-bold text-white">{{ format_rupiah($grandTotal['1_30']) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-yellow-500/30 rounded-xl p-4">
                <p class="text-[10px] font-mono text-yellow-400 uppercase tracking-widest mb-1">31 - 60 Hari</p>
                <p class="font-mono text-lg font-bold text-white">{{ format_rupiah($grandTotal['31_60']) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-orange-500/30 rounded-xl p-4">
                <p class="text-[10px] font-mono text-orange-400 uppercase tracking-widest mb-1">61 - 90 Hari</p>
                <p class="font-mono text-lg font-bold text-white">{{ format_rupiah($grandTotal['61_90']) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-red-500/30 rounded-xl p-4">
                <p class="text-[10px] font-mono text-red-400 uppercase tracking-widest mb-1">&gt; 90 Hari</p>
                <p class="font-mono text-lg font-bold text-white">{{ format_rupiah($grandTotal['over_90']) }}</p>
            </div>
        </div>

        {{-- Tabel Aging AP --}}
        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-3 border-b border-navy-800">
                <p class="text-sm text-navy-300">
                    Per Tanggal: <span class="text-white font-mono">{{ format_tanggal($asOfDate) }}</span>
                </p>
            </div>

            @if($grouped->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Supplier</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Belum JT</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">1-30</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">31-60</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">61-90</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">&gt; 90</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($grouped as $row)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm text-white font-medium">{{ $row['supplier']->nama ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-navy-300">{{ $row['current'] > 0 ? format_rupiah($row['current']) : '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-blue-400">{{ $row['1_30'] > 0 ? format_rupiah($row['1_30']) : '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-yellow-400">{{ $row['31_60'] > 0 ? format_rupiah($row['31_60']) : '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-orange-400">{{ $row['61_90'] > 0 ? format_rupiah($row['61_90']) : '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-red-400">{{ $row['over_90'] > 0 ? format_rupiah($row['over_90']) : '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono font-bold text-gold-500">{{ format_rupiah($row['total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-navy-950/50 border-t-2 border-gold-500/30">
                            <tr>
                                <td class="px-5 py-3 text-sm font-bold text-white uppercase tracking-wider">Grand Total</td>
                                <td class="px-5 py-3 text-sm text-right font-mono font-bold text-white">{{ format_rupiah($grandTotal['current']) }}</td>
                                <td class="px-5 py-3 text-sm text-right font-mono font-bold text-white">{{ format_rupiah($grandTotal['1_30']) }}</td>
                                <td class="px-5 py-3 text-sm text-right font-mono font-bold text-white">{{ format_rupiah($grandTotal['31_60']) }}</td>
                                <td class="px-5 py-3 text-sm text-right font-mono font-bold text-white">{{ format_rupiah($grandTotal['61_90']) }}</td>
                                <td class="px-5 py-3 text-sm text-right font-mono font-bold text-white">{{ format_rupiah($grandTotal['over_90']) }}</td>
                                <td class="px-5 py-3 text-sm text-right font-mono font-bold text-gold-500">{{ format_rupiah($grandTotal['total']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <x-atelier.empty-state title="Tidak ada hutang" subtitle="Tidak ada AP yang belum lunas per tanggal ini." />
            @endif
        </x-atelier.card>
    </div>
</x-app-layout>