<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Pembayaran (Payment)</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">
        <x-atelier.page-header
            segment="PAY"
            title="Pembayaran"
            subtitle="Daftar penerimaan dari customer dan pengeluaran ke supplier"
            action="Terima Pembayaran"
            :actionUrl="route('finance.payments.create-in')"
        />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-navy-900/50 border border-green-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Penerimaan Hari Ini</p>
                <p class="font-serif text-2xl font-bold text-white">{{ format_rupiah($stats['in_today']) }}</p>
            </div>
            <div class="bg-navy-900/50 border border-red-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-red-400 uppercase tracking-widest mb-1">Pengeluaran Hari Ini</p>
                <p class="font-serif text-2xl font-bold text-white">{{ format_rupiah($stats['out_today']) }}</p>
            </div>
        </div>

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800 flex flex-wrap gap-3">
                <form method="GET" class="flex gap-3 flex-wrap w-full">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. Payment / Customer..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                    <select name="direction" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                        <option value="">Semua Arah</option>
                        <option value="in" @selected(request('direction') == 'in')>Masuk (In)</option>
                        <option value="out" @selected(request('direction') == 'out')>Keluar (Out)</option>
                    </select>
                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                </form>
            </div>

            @if($payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">No. Payment</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Tanggal</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Pihak</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Metode</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Total</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($payments as $p)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono">
                                        <span class="{{ $p->direction === 'in' ? 'text-green-400' : 'text-red-400' }}">[{{ strtoupper($p->direction) }}]</span>
                                        {{ $p->payment_number }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ format_tanggal($p->payment_date) }}</td>
                                    <td class="px-5 py-3 text-sm text-white">{{ $p->customer->nama ?? $p->supplier->nama ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ ucfirst(str_replace('_', ' ', $p->method)) }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ format_rupiah($p->total_amount) }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <x-atelier.button :href="route('finance.payments.show', $p)" variant="ghost" size="sm">Lihat</x-atelier.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 border-t border-navy-800">{{ $payments->links() }}</div>
            @else
                <x-atelier.empty-state title="Belum ada pembayaran" subtitle="Catat pembayaran pertama dari customer." />
            @endif
        </x-atelier.card>
    </div>
</x-app-layout>