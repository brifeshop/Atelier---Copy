<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Neraca Saldo</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="REPORT"
            title="Neraca Saldo (Trial Balance)"
            subtitle="Ringkasan total debit & kredit per akun"
        />

        {{-- Filter Periode --}}
        <x-atelier.card :brackets="true">
            <form method="GET" action="{{ route('finance.reports.trial-balance') }}" class="flex gap-3 flex-wrap items-end">
                <div>
                    <label class="text-[10px] font-mono text-navy-400 uppercase tracking-widest block mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                        class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-[10px] font-mono text-navy-400 uppercase tracking-widest block mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                        class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                </div>
                <x-atelier.button type="submit" variant="primary">Tampilkan</x-atelier.button>
                <a href="{{ route('finance.reports.trial-balance') }}" class="text-sm text-navy-400 hover:text-gold-500 px-3 py-2">Reset</a>
            </form>
        </x-atelier.card>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-navy-900/50 border border-blue-500/30 rounded-xl p-4">
                <p class="text-[10px] font-mono text-blue-400 uppercase tracking-widest mb-1">Aset</p>
                <p class="font-mono text-lg font-bold text-white">{{ format_rupiah($summary['asset']) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-red-500/30 rounded-xl p-4">
                <p class="text-[10px] font-mono text-red-400 uppercase tracking-widest mb-1">Liabilitas</p>
                <p class="font-mono text-lg font-bold text-white">{{ format_rupiah($summary['liability']) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-purple-500/30 rounded-xl p-4">
                <p class="text-[10px] font-mono text-purple-400 uppercase tracking-widest mb-1">Ekuitas</p>
                <p class="font-mono text-lg font-bold text-white">{{ format_rupiah($summary['equity']) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-green-500/30 rounded-xl p-4">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Pendapatan</p>
                <p class="font-mono text-lg font-bold text-white">{{ format_rupiah($summary['revenue']) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-yellow-500/30 rounded-xl p-4">
                <p class="text-[10px] font-mono text-yellow-400 uppercase tracking-widest mb-1">Beban</p>
                <p class="font-mono text-lg font-bold text-white">{{ format_rupiah($summary['expense']) }}</p>
            </div>
        </div>

        {{-- Tabel Trial Balance --}}
        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-3 border-b border-navy-800 flex items-center justify-between">
                <p class="text-sm text-navy-300">
                    Periode: <span class="text-white font-mono">{{ format_tanggal($startDate) }}</span>
                    s/d <span class="text-white font-mono">{{ format_tanggal($endDate) }}</span>
                </p>
                @if(bccomp((string) $grandTotalDebit, (string) $grandTotalCredit, 2) === 0)
                    <span class="text-xs px-2 py-1 rounded-full border bg-green-500/10 border-green-500/30 text-green-400">Balance ✓</span>
                @else
                    <span class="text-xs px-2 py-1 rounded-full border bg-red-500/10 border-red-500/30 text-red-400">Tidak Balance ✗</span>
                @endif
            </div>

            @if($rows->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Kode</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Nama Akun</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Tipe</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Debit</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Kredit</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($rows as $row)
                                @if($row['total_debit'] == 0 && $row['total_credit'] == 0)
                                    @continue
                                @endif
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-2 text-sm font-mono text-gold-500">{{ $row['code'] }}</td>
                                    <td class="px-5 py-2 text-sm text-white">{{ $row['name'] }}</td>
                                    <td class="px-5 py-2 text-xs text-navy-400 uppercase">{{ $row['type'] }}</td>
                                    <td class="px-5 py-2 text-sm text-right font-mono text-white">
                                        {{ $row['total_debit'] > 0 ? format_rupiah($row['total_debit']) : '-' }}
                                    </td>
                                    <td class="px-5 py-2 text-sm text-right font-mono text-white">
                                        {{ $row['total_credit'] > 0 ? format_rupiah($row['total_credit']) : '-' }}
                                    </td>
                                    <td class="px-5 py-2 text-sm text-right font-mono font-bold {{ $row['saldo'] < 0 ? 'text-red-400' : 'text-gold-500' }}">
                                        {{ format_rupiah($row['saldo']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-navy-950/50 border-t-2 border-gold-500/30">
                            <tr>
                                <td colspan="3" class="px-5 py-3 text-sm font-bold text-white uppercase tracking-wider">Grand Total</td>
                                <td class="px-5 py-3 text-sm text-right font-mono font-bold text-gold-500">{{ format_rupiah($grandTotalDebit) }}</td>
                                <td class="px-5 py-3 text-sm text-right font-mono font-bold text-gold-500">{{ format_rupiah($grandTotalCredit) }}</td>
                                <td class="px-5 py-3 text-right text-xs {{ bccomp((string) $grandTotalDebit, (string) $grandTotalCredit, 2) === 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ bccomp((string) $grandTotalDebit, (string) $grandTotalCredit, 2) === 0 ? 'BALANCE' : 'SELISIH' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <x-atelier.empty-state title="Belum ada transaksi" subtitle="Belum ada jurnal pada periode ini." />
            @endif
        </x-atelier.card>
    </div>
</x-app-layout>