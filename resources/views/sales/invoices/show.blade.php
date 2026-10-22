<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Detail Invoice</h2>
        <p class="text-sm text-navy-400 mt-1">{{ $invoice->invoice_number }}</p>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        {{-- Action Bar: Status & Tombol Cetak --}}
        <div class="bg-navy-900/50 border border-navy-800 rounded-xl p-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-sm text-navy-400">Status:</span>
                <span class="text-sm font-bold uppercase {{ $invoice->isPaid() ? 'text-green-400' : 'text-red-400' }}">
                    {{ $invoice->status }}
                </span>
            </div>
            
            <div class="flex flex-wrap gap-2">
                {{-- Tombol Cetak --}}
                <a href="{{ route('sales.invoices.print', $invoice) }}" target="_blank" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-navy-900 bg-gold-500 border border-gold-500 rounded-lg hover:bg-gold-400 transition shadow-lg shadow-gold-500/20">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak Invoice
                </a>

                {{-- Tombol Kembali --}}
                <a href="{{ route('sales.invoices.index') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-navy-300 hover:text-white transition">
                    Kembali
                </a>

                {{-- Tombol Tandai Lunas / Batal --}}
                @if(!$invoice->isPaid() && $invoice->status !== 'cancelled')
                    <form method="POST" action="{{ route('sales.invoices.mark-paid', $invoice) }}" onsubmit="return confirm('Tandai Invoice ini Lunas?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-navy-900 bg-green-500 hover:bg-green-400 rounded-lg transition">
                            Tandai Lunas
                        </button>
                    </form>
                    <form method="POST" action="{{ route('sales.invoices.cancel', $invoice) }}" onsubmit="return confirm('Batalkan Invoice ini?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-500 rounded-lg transition">
                            Batalkan
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <x-atelier.card title="Informasi Invoice" :brackets="true">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Customer</p>
                        <p class="text-base text-white font-medium">{{ $invoice->customer->nama ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Delivery Order</p>
                        <a href="{{ route('sales.delivery-orders.show', $invoice->deliveryOrder) }}" class="text-sm text-gold-500 font-mono hover:underline">
                            {{ $invoice->deliveryOrder->do_number ?? '-' }}
                        </a>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Tgl Invoice</p>
                            <p class="text-sm text-white">{{ format_tanggal($invoice->invoice_date) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-400 uppercase tracking-widest mb-1">Jatuh Tempo</p>
                            <p class="text-sm text-white">{{ format_tanggal($invoice->due_date) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </x-atelier.card>

        <x-atelier.card title="Item Tagihan" :brackets="true" padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-navy-950/50 border-b border-navy-800">
                        <tr>
                            <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Produk</th>
                            <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Qty</th>
                            <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Harga</th>
                            <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-800">
                        @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-5 py-3 text-sm text-white">{{ $item->description }}</td>
                                <td class="px-5 py-3 text-sm text-right font-mono text-navy-300">{{ number_format($item->qty, 2) }}</td>
                                <td class="px-5 py-3 text-sm text-right font-mono text-navy-300">{{ format_rupiah($item->unit_price) }}</td>
                                <td class="px-5 py-3 text-sm text-right font-mono text-white font-semibold">{{ format_rupiah($item->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-navy-950/50 border-t border-navy-800">
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-right text-sm font-semibold text-navy-300">Total Tagihan:</td>
                            <td class="px-5 py-3 text-right font-mono font-bold text-gold-500">{{ format_rupiah($invoice->total_amount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-atelier.card>
    </div>
</x-app-layout>