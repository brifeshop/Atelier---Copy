<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Piutang Usaha (AR)</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="AR"
            title="Piutang Usaha"
            subtitle="Daftar tagihan customer yang belum lunas"
        />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Total AR</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-red-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-red-400 uppercase tracking-widest mb-1">Belum Dibayar</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['open'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-yellow-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-yellow-400 uppercase tracking-widest mb-1">Sebagian</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['partial'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-gold-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-gold-500 uppercase tracking-widest mb-1">Total Outstanding</p>
                <p class="font-serif text-2xl font-bold text-white">{{ format_rupiah($stats['outstanding']) }}</p>
            </div>
        </div>

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('finance.ar.index') }}" class="flex gap-3 flex-wrap">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari AR Number / Customer / Invoice..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:outline-none">
                    <select name="status" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                        <option value="">Semua Status</option>
                        <option value="open" @selected(request('status') == 'open')>Open</option>
                        <option value="partial" @selected(request('status') == 'partial')>Partial</option>
                        <option value="paid" @selected(request('status') == 'paid')>Paid</option>
                        <option value="void" @selected(request('status') == 'void')>Void</option>
                    </select>
                    <select name="customer_id" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                        <option value="">Semua Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->nama }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white">
                        <input type="checkbox" name="overdue" value="1" @checked(request('overdue')) class="accent-gold-500">
                        <span class="text-navy-300">Jatuh tempo</span>
                    </label>
                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                </form>
            </div>

            @if($arInvoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">AR Number</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Customer</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Invoice</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Jatuh Tempo</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Total</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Outstanding</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Status</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($arInvoices as $ar)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $ar->ar_number }}</td>
                                    <td class="px-5 py-3 text-sm text-white font-medium">{{ $ar->customer->nama ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300 font-mono">{{ $ar->salesInvoice->invoice_number ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm {{ $ar->due_date < now() && in_array($ar->status, ['open','partial']) ? 'text-red-400 font-bold' : 'text-navy-300' }}">
                                        {{ format_tanggal($ar->due_date) }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ format_rupiah($ar->total_amount) }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ format_rupiah($ar->outstanding) }}</td>
                                    <td class="px-5 py-3">
                                        @php
                                            $colors = [
                                                'open'    => 'bg-red-500/10 border-red-500/30 text-red-400',
                                                'partial' => 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
                                                'paid'    => 'bg-green-500/10 border-green-500/30 text-green-400',
                                                'void'    => 'bg-navy-800 border-navy-700 text-navy-400',
                                            ];
                                        @endphp
                                        <span class="text-xs px-2 py-1 rounded-full border {{ $colors[$ar->status] ?? '' }}">
                                            {{ ucfirst($ar->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <x-atelier.button :href="route('finance.ar.show', $ar)" variant="ghost" size="sm">Lihat</x-atelier.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($arInvoices->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">{{ $arInvoices->links() }}</div>
                @endif
            @else
                <x-atelier.empty-state title="Belum ada AR" subtitle="AR otomatis dibuat saat Sales Invoice diterbitkan." />
            @endif
        </x-atelier.card>
    </div>
</x-app-layout>