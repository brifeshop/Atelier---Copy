<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Delivery Order</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M9A"
            title="Delivery Order"
            subtitle="Surat jalan pengiriman produk ke customer"
            action="Buat DO"
            :actionUrl="route('sales.delivery-orders.create')"
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

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Total DO</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-5">
                <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Draft / Ready</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['pending'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-blue-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-blue-400 uppercase tracking-widest mb-1">Shipped</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['shipped'] }}</p>
            </div>
            <div class="bg-navy-900/50 border border-green-500/30 rounded-xl p-5">
                <p class="text-[10px] font-mono text-green-400 uppercase tracking-widest mb-1">Delivered</p>
                <p class="font-serif text-3xl font-bold text-white">{{ $stats['delivered'] }}</p>
            </div>
        </div>

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('sales.delivery-orders.index') }}" class="flex gap-3 flex-wrap">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nomor DO, SO, customer..."
                        class="flex-1 min-w-[200px] px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                    >
                    <select name="status" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                        <option value="">Semua Status</option>
                        <option value="draft" @selected(request('status') == 'draft')>Draft</option>
                        <option value="ready" @selected(request('status') == 'ready')>Ready</option>
                        <option value="shipped" @selected(request('status') == 'shipped')>Shipped</option>
                        <option value="delivered" @selected(request('status') == 'delivered')>Delivered</option>
                        <option value="cancelled" @selected(request('status') == 'cancelled')>Cancelled</option>
                    </select>
                    <select name="customer_id" class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                        <option value="">Semua Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->nama }}</option>
                        @endforeach
                    </select>
                    <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                    @if(request()->hasAny(['search', 'status', 'customer_id']))
                        <x-atelier.button :href="route('sales.delivery-orders.index')" variant="ghost">Reset</x-atelier.button>
                    @endif
                </form>
            </div>

            @if($deliveryOrders->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">No. DO</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Sales Order</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Customer</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Tgl Kirim</th>
                                <th class="px-5 py-3 text-center text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Item</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Status</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($deliveryOrders as $do)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $do->do_number }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300 font-mono">{{ $do->salesOrder->so_number ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-white font-medium">{{ $do->customer->nama ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ format_tanggal($do->delivery_date) }}</td>
                                    <td class="px-5 py-3 text-sm text-center text-navy-300">{{ $do->items_count }}</td>
                                    <td class="px-5 py-3">
                                        <x-sales.do-status-badge :status="$do->status" />
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-atelier.button :href="route('sales.delivery-orders.show', $do)" variant="ghost" size="sm">Lihat</x-atelier.button>
                                            @if($do->isEditable())
                                                <x-atelier.button :href="route('sales.delivery-orders.edit', $do)" variant="secondary" size="sm">Edit</x-atelier.button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($deliveryOrders->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">
                        {{ $deliveryOrders->links() }}
                    </div>
                @endif
            @else
                <x-atelier.empty-state
                    title="Belum ada Delivery Order"
                    subtitle="Mulai dengan membuat DO dari Sales Order yang sudah confirmed."
                    action="Buat DO"
                    :actionUrl="route('sales.delivery-orders.create')"
                />
            @endif
        </x-atelier.card>

    </div>
</x-app-layout>