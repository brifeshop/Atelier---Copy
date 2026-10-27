<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Detail Pembayaran</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">
        <x-atelier.page-header
            :segment="$payment->payment_number"
            :title="'Pembayaran ' . ucfirst($payment->direction)"
            :subtitle="format_tanggal($payment->payment_date)"
        />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Info --}}
                <x-atelier.card title="Informasi" :brackets="true">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-[10px] text-navy-500 uppercase mb-1">No. Payment</p>
                            <p class="font-mono text-gold-500">{{ $payment->payment_number }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-navy-500 uppercase mb-1">Metode</p>
                            <p class="text-white">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-navy-500 uppercase mb-1">Customer</p>
                            <p class="text-white">{{ $payment->customer->nama ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-navy-500 uppercase mb-1">Akun Kas/Bank</p>
                            <p class="text-white">{{ $payment->cashAccount->code }} - {{ $payment->cashAccount->name }}</p>
                        </div>
                        @if($payment->reference_number)
                        <div class="col-span-2">
                            <p class="text-[10px] text-navy-500 uppercase mb-1">Referensi</p>
                            <p class="text-white">{{ $payment->reference_number }}</p>
                        </div>
                        @endif
                    </div>
                </x-atelier.card>

                {{-- Alokasi AR --}}
                <x-atelier.card title="Alokasi Piutang" :brackets="true" padding="p-0">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase">AR Number</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase">Invoice</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase">Dialokasikan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($payment->allocations as $alloc)
                                <tr>
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $alloc->arInvoice->ar_number ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ $alloc->arInvoice->salesInvoice->invoice_number ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ format_rupiah($alloc->amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-atelier.card>

                {{-- Jurnal --}}
                <x-atelier.card title="Jurnal Akuntansi" :brackets="true" padding="p-0">
                    @if($payment->journal)
                        <div class="px-5 py-3 border-b border-navy-800 text-sm">
                            <span class="font-mono text-gold-500">{{ $payment->journal->journal_number }}</span>
                        </div>
                        <table class="w-full">
                            <thead class="bg-navy-950/50 border-b border-navy-800">
                                <tr>
                                    <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase">Akun</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase">Debit</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-navy-800">
                                @foreach($payment->journal->items as $item)
                                    <tr>
                                        <td class="px-5 py-3 text-sm text-white">
                                            <span class="font-mono text-gold-500">{{ $item->account->code }}</span> {{ $item->account->name }}
                                        </td>
                                        <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ $item->debit > 0 ? format_rupiah($item->debit) : '-' }}</td>
                                        <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ $item->credit > 0 ? format_rupiah($item->credit) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </x-atelier.card>
            </div>

            {{-- Ringkasan Kanan --}}
            <div class="space-y-6">
                <x-atelier.card title="Total Diterima" :brackets="true">
                    <p class="font-serif text-3xl font-bold text-gold-500">{{ format_rupiah($payment->total_amount) }}</p>
                </x-atelier.card>
                <x-atelier.button :href="route('finance.payments.index')" variant="secondary" class="w-full">Kembali ke Daftar</x-atelier.button>
            </div>
        </div>
    </div>
</x-app-layout>