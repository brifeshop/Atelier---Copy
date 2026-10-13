<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Purchase Order</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M4B"
            title="Purchase Order"
            subtitle="Daftar pesanan ke supplier"
            action="Buat PO"
            :actionUrl="route('purchasing.purchase-orders.create')"
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

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Total PO</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Draft</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['draft'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-blue-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-blue-400 uppercase tracking-widest mb-1">Sent</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['sent'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-green-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Received</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['received'] }}</p>
            </div>
        </div>

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('purchasing.purchase-orders.index') }}" class="flex gap-3 flex-wrap">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nomor PO, supplier..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                    >
                    <select name="status" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                        <option value="">Semua Status</option>
                        <option value="draft" @selected(request('status') == 'draft')>Draft</option>
                        <option value="sent" @selected(request('status') == 'sent')>Sent</option>
                        <option value="partial" @selected(request('status') == 'partial')>Partial</option>
                        <option value="received" @selected(request('status') == 'received')>Received</option>
                        <option value="closed" @selected(request('status') == 'closed')>Closed</option>
                        <option value="cancelled" @selected(request('status') == 'cancelled')>Cancelled</option>
                    </select>
                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                    @if(request()->hasAny(['search', 'status']))
                        <x-atelier.button :href="route('purchasing.purchase-orders.index')" variant="ghost">Reset</x-atelier.button>
                    @endif
                </form>
            </div>

            @if($items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <x-sortable-header column="po_number" label="No. PO" align="left" />
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Supplier</th>
                                <x-sortable-header column="date" label="Tanggal" align="left" />
                                <x-sortable-header column="total_amount" label="Total" align="right" />
                                <x-sortable-header column="status" label="Status" align="left" />
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($items as $po)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $po->po_number }}</td>
                                    <td class="px-5 py-3 text-sm text-white font-medium">
                                        {{ $po->supplier->nama ?? '-' }}
                                        @if($po->supplier)
                                            <br><span class="text-xs text-navy-500">{{ $po->supplier->kode }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-sm text-navy-300">
                                        {{ format_tanggal($po->date) }}
                                        @if($po->delivery_date)
                                            <br><span class="text-xs text-navy-500">Kirim: {{ format_tanggal($po->delivery_date) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-white">
                                        {{ format_rupiah($po->total_amount) }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center gap-1.5 text-xs px-2 py-1 rounded-full border {{ $po->status_color }}">
                                            {{ $po->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-atelier.button :href="route('purchasing.purchase-orders.show', $po)" variant="ghost" size="sm">Lihat</x-atelier.button>
                                            @if($po->canEdit())
                                                <x-atelier.button :href="route('purchasing.purchase-orders.edit', $po)" variant="secondary" size="sm">Edit</x-atelier.button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($items->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">
                        {{ $items->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <x-atelier.empty-state
                    title="Belum ada Purchase Order"
                    subtitle="Mulai dengan membuat PO pertama."
                    action="Buat PO"
                    :actionUrl="route('purchasing.purchase-orders.create')"
                />
            @endif
        </x-atelier.card>

    </div>
</x-app-layout>