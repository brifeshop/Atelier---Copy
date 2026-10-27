<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Buku Besar</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="REPORT"
            title="Buku Besar (General Ledger)"
            subtitle="Riwayat mutasi transaksi per akun"
        />

        {{-- Filter --}}
        <x-atelier.card :brackets="true">
            <form method="GET" action="{{ route('finance.reports.general-ledger') }}" class="flex gap-3 flex-wrap items-end">
                <div>
                    <label class="text-[10px] font-mono text-navy-400 uppercase tracking-widest block mb-1">Akun</label>
                    <select name="account_id" required
                        class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none w-64">
                        <option value="">-- Pilih Akun --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" @selected($accountId == $acc->id)>
                                {{ $acc->code }} - {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
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
                <a href="{{ route('finance.reports.general-ledger') }}" class="text-sm text-navy-400 hover:text-gold-500 px-3 py-2">Reset</a>
            </form>
        </x-atelier.card>

        @if($selectedAccount)
            {{-- Info Akun --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-navy-900/50 border border-gold-500/30 rounded-xl p-5">
                    <p class="text-[10px] font-mono text-gold-500 uppercase tracking-widest mb-1">Akun</p>
                    <p class="font-mono text-sm text-white">{{ $selectedAccount->code }}</p>
                    <p class="text-lg font-bold text-white mt-1">{{ $selectedAccount->name }}</p>
                </div>
                <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                    <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Saldo Awal</p>
                    <p class="font-serif text-2xl font-bold text-white">{{ format_rupiah($saldoAwal) }}</p>
                </div>
                <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                    <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Saldo Akhir</p>
                    <p class="font-serif text-2xl font-bold {{ $saldoAkhir < 0 ? 'text-red-400' : 'text-gold-500' }}">{{ format_rupiah($saldoAkhir) }}</p>
                </div>
            </div>

            {{-- Tabel Mutasi --}}
            <x-atelier.card :brackets="true" padding="p-0">
                <div class="px-5 py-3 border-b border-navy-800">
                    <p class="text-sm text-navy-300">
                        Periode: <span class="text-white font-mono">{{ format_tanggal($startDate) }}</span>
                        s/d <span class="text-white font-mono">{{ format_tanggal($endDate) }}</span>
                    </p>
                </div>

                @if($mutations->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-navy-950/50 border-b border-navy-800">
                                <tr>
                                    <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Tanggal</th>
                                    <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">No. Jurnal</th>
                                    <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Deskripsi</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Debit</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Kredit</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-navy-800">
                                {{-- Saldo Awal --}}
                                <tr class="bg-navy-950/30">
                                    <td colspan="5" class="px-5 py-2 text-sm font-semibold text-navy-300 italic">Saldo Awal Periode</td>
                                    <td class="px-5 py-2 text-sm text-right font-mono font-bold text-white">{{ format_rupiah($saldoAwal) }}</td>
                                </tr>
                                
                                @php $runningSaldo = $saldoAwal; @endphp
                                @foreach($mutations as $m)
                                    @php
                                        if ($selectedAccount->normal_balance === 'debit') {
                                            $runningSaldo += ((float) $m->debit - (float) $m->credit);
                                        } else {
                                            $runningSaldo += ((float) $m->credit - (float) $m->debit);
                                        }
                                    @endphp
                                    <tr class="hover:bg-navy-800/30 transition">
                                        <td class="px-5 py-3 text-sm text-navy-300">{{ format_tanggal($m->journal_date) }}</td>
                                        <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $m->journal_number }}</td>
                                        <td class="px-5 py-3 text-sm text-white">{{ $m->description }}</td>
                                        <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ $m->debit > 0 ? format_rupiah($m->debit) : '-' }}</td>
                                        <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ $m->credit > 0 ? format_rupiah($m->credit) : '-' }}</td>
                                        <td class="px-5 py-3 text-sm text-right font-mono font-bold text-gold-500">{{ format_rupiah($runningSaldo) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-navy-950/50 border-t-2 border-gold-500/30">
                                <tr>
                                    <td colspan="3" class="px-5 py-3 text-sm font-bold text-white uppercase tracking-wider">Total Mutasi</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono font-bold text-white">{{ format_rupiah($mutations->sum('debit')) }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono font-bold text-white">{{ format_rupiah($mutations->sum('credit')) }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono font-bold text-gold-500">{{ format_rupiah($runningSaldo) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <x-atelier.empty-state title="Tidak ada mutasi" subtitle="Akun ini tidak memiliki transaksi pada periode ini." />
                @endif
            </x-atelier.card>
        @else
            <x-atelier.card :brackets="true">
                <x-atelier.empty-state title="Belum ada akun dipilih" subtitle="Silakan pilih akun pada form di atas untuk melihat Buku Besar." />
            </x-atelier.card>
        @endif
    </div>
</x-app-layout>