<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Finance Dashboard</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="FIN"
            title="Finance Dashboard"
            subtitle="Ringkasan piutang, hutang, dan arus kas"
        />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Row 1: AR --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-atelier.stat-card
                label="AR Outstanding"
                :value="format_rupiah($arOutstanding)"
                :desc="$arOpen . ' invoice belum lunas'"
                accent="true"
                icon="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                :href="route('finance.ar.index')"
            />
            <x-atelier.stat-card
                label="AR Jatuh Tempo"
                :value="$arOverdue . ' invoice'"
                desc="Perlu ditagih segera"
                :trend="$arOverdue > 0 ? 'Overdue' : null"
                :trendType="$arOverdue > 0 ? 'down' : 'neutral'"
                icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                :href="route('finance.ar.index', ['overdue' => 1])"
            />
            <x-atelier.stat-card
                label="AP Outstanding"
                :value="format_rupiah($apOutstanding)"
                :desc="$apOpen . ' bill belum dibayar'"
                icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
            <x-atelier.stat-card
                label="Payment Hari Ini"
                :value="format_rupiah($payInToday)"
                :desc="'Keluar: ' . format_rupiah($payOutToday)"
                icon="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
            />
        </div>

        {{-- Row 2: Recent AR & Recent Payments --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Recent AR --}}
            <x-atelier.card title="AR Terbaru" subtitle="5 piutang terakhir" :brackets="true" padding="p-0">
                @if($recentAr->count() > 0)
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">AR Number</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Customer</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($recentAr as $ar)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono">
                                        <a href="{{ route('finance.ar.show', $ar) }}" class="text-gold-500 hover:text-gold-400">{{ $ar->ar_number }}</a>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-white">{{ $ar->customer->nama ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ format_rupiah($ar->outstanding) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-atelier.empty-state title="Belum ada AR" subtitle="AR otomatis dibuat saat Sales Invoice diterbitkan." />
                @endif
            </x-atelier.card>

            {{-- Recent Payments --}}
            <x-atelier.card title="Pembayaran Terbaru" subtitle="5 transaksi terakhir" :brackets="true" padding="p-0">
                @if($recentPayments->count() > 0)
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Payment</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Pihak</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($recentPayments as $p)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $p->payment_number }}</td>
                                    <td class="px-5 py-3 text-sm text-white">
                                        {{ $p->customer->nama ?? $p->supplier->nama ?? '-' }}
                                        <span class="text-[10px] uppercase {{ $p->direction === 'in' ? 'text-green-400' : 'text-red-400' }}">
                                            ({{ $p->direction }})
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ format_rupiah($p->total_amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-atelier.empty-state title="Belum ada pembayaran" subtitle="Catat pembayaran customer / supplier." />
                @endif
            </x-atelier.card>
        </div>
    </div>
</x-app-layout>