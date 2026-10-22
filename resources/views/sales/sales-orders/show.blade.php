<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Detail Sales Order</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M3"
            title="{{ $item->so_number }}"
            subtitle="{{ $item->customer->nama ?? '-' }}"
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

        {{-- STATUS + ACTIONS --}}
        <x-atelier.card :brackets="true">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Status</p>
                    @php
                        $statusStyles = [
                            'draft' => 'bg-navy-800 text-navy-300 border-navy-700',
                            'confirmed' => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                            'in_production' => 'bg-gold-500/10 text-gold-500 border-gold-500/30',
                            'delivered' => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                            'paid' => 'bg-green-500/10 text-green-400 border-green-500/30',
                            'closed' => 'bg-green-500/10 text-green-400 border-green-500/30',
                            'cancelled' => 'bg-red-500/10 text-red-400 border-red-500/30',
                        ];
                        $style = $statusStyles[$item->status] ?? 'bg-navy-800 text-navy-300 border-navy-700';
                    @endphp
                    <span class="inline-flex items-center gap-2 text-sm px-3 py-1.5 rounded-full border {{ $style }}">
                        {{ $item->status_label }}
                    </span>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @if($item->canEdit())
                        <x-atelier.button :href="route('sales.sales-orders.edit', $item)" variant="secondary" size="sm">
                            Edit SO
                        </x-atelier.button>
                    @endif

                    @if($item->canConfirm())
                        {{-- PERBAIKAN: Pakai URL langsung --}}
                        <form method="POST" action="{{ url('sales/sales-orders/' . $item->id . '/status') }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="confirmed">
                            <x-atelier.button type="submit" variant="primary" size="sm">
                                Konfirmasi SO
                            </x-atelier.button>
                        </form>
                    @endif

                    @if(!in_array($item->status, ['draft', 'closed', 'cancelled']))
                        {{-- PERBAIKAN: Pakai URL langsung --}}
                        <form method="POST" action="{{ url('sales/sales-orders/' . $item->id . '/status') }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <select name="status" onchange="this.form.submit()"
                                    class="px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-xs text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                                <option value="">Ubah Status →</option>
                                @if($item->status === 'confirmed')
                                    <option value="in_production">In Production</option>
                                @endif
                                @if($item->status === 'in_production')
                                    <option value="delivered">Delivered</option>
                                @endif
                                @if($item->status === 'delivered')
                                    <option value="paid">Paid</option>
                                @endif
                                @if($item->status === 'paid')
                                    <option value="closed">Closed</option>
                                @endif
                            </select>
                        </form>
                    @endif

                    @if($item->canCancel())
                        {{-- PERBAIKAN: Pakai URL langsung --}}
                        <form method="POST" action="{{ url('sales/sales-orders/' . $item->id . '/status') }}" class="inline"
                              onsubmit="return confirm('Yakin batalkan SO ini?')">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <x-atelier.button type="submit" variant="danger" size="sm">
                                Batalkan
                            </x-atelier.button>
                        </form>
                    @endif

                    @if($item->status === 'draft')
                        <form method="POST" action="{{ route('sales.sales-orders.destroy', $item) }}" class="inline"
                              onsubmit="return confirm('Yakin hapus SO ini?')">
                            @csrf
                            @method('DELETE')
                            <x-atelier.button type="submit" variant="danger" size="sm">
                                Hapus
                            </x-atelier.button>
                        </form>
                    @endif

                    <x-atelier.button :href="route('sales.sales-orders.index')" variant="ghost" size="sm">
                        Kembali
                    </x-atelier.button>
                </div>
            </div>
        </x-atelier.card>

        {{-- INFO --}}
        <x-atelier.card title="Informasi SO" :brackets="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">No. SO</p>
                    <p class="text-sm text-gold-500 font-mono">{{ $item->so_number }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Customer</p>
                    <p class="text-sm text-white font-medium">{{ $item->customer->nama ?? '-' }}</p>
                    @if($item->customer)
                        <p class="text-xs text-navy-500">{{ $item->customer->kode }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Sales Person</p>
                    <p class="text-sm text-navy-300">{{ $item->salesPerson->nama ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Tanggal Pesan</p>
                    <p class="text-sm text-navy-300">{{ format_tanggal($item->order_date) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Target Kirim</p>
                    <p class="text-sm text-navy-300">{{ $item->delivery_date ? format_tanggal($item->delivery_date) : '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Dibuat Oleh</p>
                    <p class="text-sm text-navy-300">{{ $item->createdBy->name ?? '-' }}</p>
                </div>
                @if($item->notes)
                    <div class="md:col-span-2">
                        <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Catatan</p>
                        <p class="text-sm text-navy-300">{{ $item->notes }}</p>
                    </div>
                @endif
            </div>
        </x-atelier.card>

        {{-- ITEMS --}}
        <x-atelier.card title="Item Pesanan" :brackets="true" padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-950/50 border-b border-navy-800">
                        <tr>
                            <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Produk</th>
                            <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Qty</th>
                            <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Harga</th>
                            <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Diskon</th>
                            <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-800">
                        @foreach($item->items as $soItem)
                            <tr>
                                <td class="px-5 py-3 text-sm text-white font-medium">
                                    {{ $soItem->product->nama ?? '-' }}
                                    @if($soItem->product)
                                        <br><span class="text-xs text-navy-500 font-mono">{{ $soItem->product->kode }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-sm text-right font-mono text-white">
                                    {{ format_angka($soItem->qty, 2) }}
                                    <span class="text-xs text-navy-500">{{ $soItem->product->unit ?? '' }}</span>
                                </td>
                                <td class="px-5 py-3 text-sm text-right font-mono text-navy-300">
                                    {{ format_rupiah($soItem->unit_price) }}
                                </td>
                                <td class="px-5 py-3 text-sm text-right font-mono text-red-400">
                                    @if($soItem->discount_percent > 0)
                                        {{ $soItem->discount_percent }}%
                                        <br><span class="text-xs">- {{ format_rupiah($soItem->discount_amount) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-sm text-right font-mono text-white font-semibold">
                                    {{ format_rupiah($soItem->subtotal) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-atelier.card>

        {{-- SUMMARY --}}
        <x-atelier.card title="Ringkasan" :brackets="true">
            <div class="max-w-md ml-auto space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-navy-400">Subtotal</span>
                    <span class="text-white font-mono">{{ format_rupiah($item->subtotal) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-navy-400">Total Diskon</span>
                    <span class="text-red-400 font-mono">- {{ format_rupiah($item->discount_amount) }}</span>
                </div>
                <div class="flex justify-between text-lg pt-3 border-t border-navy-800">
                    <span class="text-white font-semibold">Total</span>
                    <span class="text-gold-500 font-mono font-bold">{{ format_rupiah($item->total_amount) }}</span>
                </div>
                <div class="flex justify-between text-sm pt-3 border-t border-navy-800">
                    <span class="text-navy-400">Komisi Sales ({{ $item->commission_rate }}%)</span>
                    <span class="text-green-400 font-mono">{{ format_rupiah($item->commission_amount) }}</span>
                </div>
            </div>
        </x-atelier.card>

        {{-- TIMELINE / AUDIT TRAIL --}}
        @if($item->statusLogs && $item->statusLogs->count() > 0)
            <x-atelier.card title="Timeline Perubahan Status" subtitle="Audit trail lengkap" :brackets="true" padding="p-0">
                <div class="p-5">
                    <div class="relative">
                        <div class="absolute left-4 top-2 bottom-2 w-px bg-navy-800"></div>

                        <div class="space-y-5">
                            @foreach($item->statusLogs as $log)
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-navy-500',
                                        'confirmed' => 'bg-blue-400',
                                        'in_production' => 'bg-gold-500',
                                        'delivered' => 'bg-purple-400',
                                        'paid' => 'bg-green-400',
                                        'closed' => 'bg-green-500',
                                        'cancelled' => 'bg-red-400',
                                    ];
                                    $dotColor = $statusColors[$log->to_status] ?? 'bg-navy-500';
                                    $isAuto = str_starts_with($log->reason ?? '', 'Auto:');
                                @endphp

                                <div class="relative flex gap-4">
                                    <div class="relative z-10 w-8 h-8 rounded-full bg-navy-900 border-2 border-navy-800 flex items-center justify-center flex-shrink-0">
                                        <div class="w-2.5 h-2.5 rounded-full {{ $dotColor }}"></div>
                                    </div>

                                    <div class="flex-1 pb-1">
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <span class="text-sm text-white font-medium">
                                                {{ $log->to_status_label }}
                                            </span>
                                            @if($isAuto)
                                                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-gold-500/10 text-gold-500 border border-gold-500/30">
                                                    AUTO
                                                </span>
                                            @else
                                                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-navy-800 text-navy-400 border border-navy-700">
                                                    MANUAL
                                                </span>
                                            @endif
                                        </div>

                                        <p class="text-xs text-navy-400 mb-1">
                                            {{ $log->reason ?? '—' }}
                                        </p>

                                        <div class="flex items-center gap-3 text-[10px] font-mono text-navy-500">
                                            <span>{{ format_tanggal_waktu($log->changed_at) }}</span>
                                            @if($log->changedBy)
                                                <span>·</span>
                                                <span>{{ $log->changedBy->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-atelier.card>
        @endif

    </div>
</x-app-layout>