<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Sales Return</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">
        <x-atelier.page-header segment="M9C" title="Sales Return" subtitle="Daftar retur penjualan dari customer" action="Buat Retur" :actionUrl="route('sales.returns.create')" />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Total Retur</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-yellow-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-yellow-400 uppercase tracking-widest mb-1">Draft</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['draft'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-green-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Selesai</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['completed'] }}</p>
            </div>
        </div>

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" class="flex gap-3 flex-wrap">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor retur, customer..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                    <select name="status" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                        <option value="">Semua Status</option>
                        <option value="draft" @selected(request('status') == 'draft')>Draft</option>
                        <option value="completed" @selected(request('status') == 'completed')>Selesai</option>
                    </select>
                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                </form>
            </div>

            @if($returns->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">No. Retur</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Customer</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Tgl Retur</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Alasan</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Total</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Status</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($returns as $ret)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $ret->return_number }}</td>
                                    <td class="px-5 py-3 text-sm text-white">{{ $ret->customer->nama ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ format_tanggal($ret->return_date) }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ $ret->reason_label }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ format_rupiah($ret->total_amount) }}</td>
                                    <td class="px-5 py-3">
                                        @php
                                            $colors = [
                                                'draft' => 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
                                                'completed' => 'bg-green-500/10 border-green-500/30 text-green-400',
                                                'cancelled' => 'bg-red-500/10 border-red-500/30 text-red-400',
                                            ];
                                        @endphp
                                        <span class="text-xs px-2 py-1 rounded-full border {{ $colors[$ret->status] ?? '' }}">
                                            {{ ucfirst($ret->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <x-atelier.button :href="route('sales.returns.show', $ret)" variant="ghost" size="sm">Lihat</x-atelier.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($returns->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">{{ $returns->links() }}</div>
                @endif
            @else
                <x-atelier.empty-state title="Belum ada Retur" subtitle="Buat retur dari halaman Detail Delivery Order." />
            @endif
        </x-atelier.card>
    </div>
</x-app-layout>