<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-serif text-2xl font-bold text-white">Detail Delivery Order</h2>
                <p class="text-sm text-navy-400 mt-1">{{ $deliveryOrder->do_number }}</p>
            </div>
            <div class="flex gap-2">
                <x-atelier.button :href="route('sales.delivery-orders.index')" variant="ghost" size="sm">
                    Kembali
                </x-atelier.button>
                
                @if($deliveryOrder->isEditable())
                    <x-atelier.button :href="route('sales.delivery-orders.edit', $deliveryOrder)" variant="secondary" size="sm">
                        Edit DO
                    </x-atelier.button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        {{-- Alert Success / Error --}}
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

        {{-- Action Bar (Tombol Status) --}}
        <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-sm text-navy-400">Status saat ini:</span>
                <x-sales.do-status-badge :status="$deliveryOrder->status" />
            </div>

            <div class="flex flex-wrap gap-2">
                {{-- Tombol Aksi DO (Mark Ready, Ship, Deliver) --}}
                @if($deliveryOrder->status === 'draft')
                    <form method="POST" action="{{ route('sales.delivery-orders.mark-ready', $deliveryOrder) }}">
                        @csrf
                        @method('PATCH')
                        <x-atelier.button type="submit" variant="primary" size="sm">
                            Tandai Siap Kirim
                        </x-atelier.button>
                    </form>
                @endif

                @if($deliveryOrder->status === 'ready')
                    <form method="POST" action="{{ route('sales.delivery-orders.ship', $deliveryOrder) }}" onsubmit="return confirm('Kirim DO ini? Stok produk akan dikurangi.')">
                        @csrf
                        @method('PATCH')
                        <x-atelier.button type="submit" variant="primary" size="sm">
                            Kirim Barang (Ship)
                        </x-atelier.button>
                    </form>
                @endif

                @if($deliveryOrder->status === 'shipped')
                    <form method="POST" action="{{ route('sales.delivery-orders.deliver', $deliveryOrder) }}" onsubmit="return confirm('Tandai DO ini sudah diterima customer?')">
                        @csrf
                        @method('PATCH')
                        <x-atelier.button type="submit" variant="primary" size="sm">
                            Tandai Diterima
                        </x-atelier.button>
                    </form>
                @endif

                @if($deliveryOrder->canBeCancelled())
                    <form method="POST" action="{{ route('sales.delivery-orders.cancel', $deliveryOrder) }}" onsubmit="return confirm('Batalkan DO ini?')">
                        @csrf
                        @method('PATCH')
                        <x-atelier.button type="submit" variant="danger" size="sm">
                            Batalkan DO
                        </x-atelier.button>
                    </form>
                @endif

                {{-- ========================================== --}}
                {{-- TOMBOL GENERATE INVOICE (BARU DITAMBAHKAN) --}}
                {{-- ========================================== --}}
                @if(in_array($deliveryOrder->status, ['shipped', 'delivered']))
                    @if($deliveryOrder->invoices->count() == 0)
                        <form method="POST" action="{{ route('sales.delivery-orders.generate-invoice', $deliveryOrder) }}">
                            @csrf
                            <x-atelier.button type="submit" variant="secondary" size="sm" class="border-gold-500 text-gold-500 hover:bg-gold-500 hover:text-navy-900">
                                Buat Invoice
                            </x-atelier.button>
                        </form>
                    @else
                        <x-atelier.button :href="route('sales.invoices.show', $deliveryOrder->invoices->first())" variant="primary" size="sm">
                            Lihat Invoice
                        </x-atelier.button>
                    @endif
                @endif
            </div>
        </div>

        {{-- Informasi DO --}}
        <x-atelier.card title="Informasi Delivery Order" :brackets="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Kolom Kiri --}}
                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Customer</p>
                        <p class="text-base text-white font-medium">{{ $deliveryOrder->customer->nama ?? '-' }}</p>
                        <p class="text-sm text-navy-300">{{ $deliveryOrder->customer->phone ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Sales Order</p>
                        <a href="{{ $deliveryOrder->salesOrder ? route('sales.sales-orders.show', $deliveryOrder->salesOrder) : '#' }}" class="text-sm text-gold-500 font-mono hover:underline">
                            {{ $deliveryOrder->salesOrder->so_number ?? '-' }}
                        </a>
                    </div>
                    <div>
                        <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Gudang Asal</p>
                        <p class="text-sm text-white">{{ $deliveryOrder->warehouse->nama ?? '-' }}</p>
                    </div>
                </div>

                {{-- Kolom Kanan --}}
                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Tanggal Kirim</p>
                        <p class="text-sm text-white">{{ format_tanggal($deliveryOrder->delivery_date) }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">No. Kendaraan</p>
                            <p class="text-sm font-mono text-white">{{ $deliveryOrder->vehicle_number ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Nama Driver</p>
                            <p class="text-sm text-white">{{ $deliveryOrder->driver_name ?? '-' }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Alamat Kirim</p>
                        <p class="text-sm text-navy-300">{{ $deliveryOrder->shipping_address ?? '-' }}</p>
                    </div>
                </div>
            </div>

            @if($deliveryOrder->notes)
                <div class="mt-6 pt-4 border-t border-navy-800">
                    <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Catatan</p>
                    <p class="text-sm text-navy-300">{{ $deliveryOrder->notes }}</p>
                </div>
            @endif
        </x-atelier.card>

        {{-- Item Pengiriman --}}
        <x-atelier.card title="Item Pengiriman" :brackets="true" padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-950/50 border-b border-navy-800">
                        <tr>
                            <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Produk</th>
                            <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Lokasi Asal</th>
                            <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Qty Pesan</th>
                            <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Qty Kirim</th>
                            <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Qty Terkirim</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-800">
                        @foreach($deliveryOrder->items as $item)
                            <tr class="hover:bg-navy-800/30 transition">
                                <td class="px-5 py-3">
                                    <p class="text-sm text-white font-medium">{{ $item->product->nama ?? '-' }}</p>
                                    <p class="text-xs font-mono text-navy-500">{{ $item->product->kode ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-3 text-sm text-navy-300">
                                    {{ $item->location->kode ?? '-' }} — {{ $item->location->nama ?? '-' }}
                                </td>
                                <td class="px-5 py-3 text-sm text-right font-mono text-navy-300">{{ number_format($item->qty_ordered, 2) }}</td>
                                <td class="px-5 py-3 text-sm text-right font-mono text-gold-500">{{ number_format($item->qty_delivered, 2) }}</td>
                                <td class="px-5 py-3 text-sm text-right font-mono {{ $item->qty_shipped > 0 ? 'text-green-400' : 'text-navy-500' }}">
                                    {{ number_format($item->qty_shipped, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-navy-950/50 border-t border-navy-800">
                        <tr>
                            <td colspan="2" class="px-5 py-3 text-right text-sm font-semibold text-navy-300">Total:</td>
                            <td class="px-5 py-3 text-right font-mono font-bold text-white">{{ number_format($deliveryOrder->total_qty, 2) }}</td>
                            <td class="px-5 py-3 text-right font-mono font-bold text-gold-500">{{ number_format($deliveryOrder->total_shipped_qty, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-atelier.card>

        {{-- Riwayat Status --}}
        <x-atelier.card title="Riwayat Status" :brackets="true" padding="p-0">
            <div class="p-5 space-y-4">
                @forelse($deliveryOrder->statusLogs as $log)
                    <div class="flex gap-4">
                        <div class="w-2 h-2 mt-1.5 rounded-full bg-gold-500 flex-shrink-0"></div>
                        <div>
                            <p class="text-sm text-white">
                                <span class="font-semibold text-gold-500">{{ ucfirst($log->to_status) }}</span>
                                @if($log->notes)
                                    — {{ $log->notes }}
                                @endif
                            </p>
                            <p class="text-xs text-navy-500 mt-1">
                                {{ format_tanggal_waktu($log->created_at) }} oleh {{ $log->changedBy->name ?? 'System' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-navy-500">Belum ada riwayat status.</p>
                @endforelse
            </div>
        </x-atelier.card>

    </div>
</x-app-layout>