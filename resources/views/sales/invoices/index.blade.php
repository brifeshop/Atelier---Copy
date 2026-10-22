<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Sales Invoice</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M9B"
            title="Sales Invoice"
            subtitle="Daftar faktur penjualan customer"
        />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Total Invoice</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-red-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-red-400 uppercase tracking-widest mb-1">Belum Lunas</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['unpaid'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-green-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Lunas</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['paid'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-gold-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-gold-500 uppercase tracking-widest mb-1">Total Omzet (Lunas)</p>
                <p class="font-serif text-2xl font-bold text-white">{{ format_rupiah($stats['total_omzet']) }}</p>
            </div>
        </div>

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('sales.invoices.index') }}" class="flex gap-3 flex-wrap">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor Invoice, Customer, DO..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:outline-none">
                    <select name="status" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                        <option value="">Semua Status</option>
                        <option value="unpaid" @selected(request('status') == 'unpaid')>Belum Lunas</option>
                        <option value="paid" @selected(request('status') == 'paid')>Lunas</option>
                        <option value="cancelled" @selected(request('status') == 'cancelled')>Dibatalkan</option>
                    </select>
                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                </form>
            </div>

            @if($invoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">No. Invoice</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Customer</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Tgl Invoice</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Jatuh Tempo</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Total</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Status</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($invoices as $inv)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $inv->invoice_number }}</td>
                                    <td class="px-5 py-3 text-sm text-white font-medium">{{ $inv->customer->nama ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ format_tanggal($inv->invoice_date) }}</td>
                                    <td class="px-5 py-3 text-sm {{ $inv->due_date < now() && !$inv->isPaid() ? 'text-red-400 font-bold' : 'text-navy-300' }}">
                                        {{ format_tanggal($inv->due_date) }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ format_rupiah($inv->total_amount) }}</td>
                                    <td class="px-5 py-3">
                                        @php
                                            $colors = [
                                                'unpaid' => 'bg-red-500/10 border-red-500/30 text-red-400',
                                                'paid' => 'bg-green-500/10 border-green-500/30 text-green-400',
                                                'cancelled' => 'bg-navy-800 border-navy-700 text-navy-400',
                                            ];
                                        @endphp
                                        <span class="text-xs px-2 py-1 rounded-full border {{ $colors[$inv->status] ?? '' }}">
                                            {{ ucfirst($inv->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <x-atelier.button :href="route('sales.invoices.show', $inv)" variant="ghost" size="sm">Lihat</x-atelier.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($invoices->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">{{ $invoices->links() }}</div>
                @endif
            @else
                <x-atelier.empty-state title="Belum ada Invoice" subtitle="Buat Invoice dari halaman Detail Delivery Order." />
            @endif
        </x-atelier.card>
    </div>
</x-app-layout>