<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Detail Piutang</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            :segment="$ar->ar_number"
            :title="'AR untuk ' . ($ar->salesInvoice->invoice_number ?? '-')"
            :subtitle="$ar->customer->nama ?? '-'"
        />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Info AR --}}
            <div class="lg:col-span-2 space-y-6">
                <x-atelier.card title="Informasi" :brackets="true">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">AR Number</p>
                            <p class="font-mono text-gold-500">{{ $ar->ar_number }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Invoice</p>
                            <p class="font-mono text-white">{{ $ar->salesInvoice->invoice_number ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Tgl Invoice</p>
                            <p class="text-white">{{ format_tanggal($ar->invoice_date) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Jatuh Tempo</p>
                            <p class="{{ $ar->due_date < now() && in_array($ar->status, ['open','partial']) ? 'text-red-400 font-bold' : 'text-white' }}">
                                {{ format_tanggal($ar->due_date) }}
                            </p>
                        </div>
                    </div>
                </x-atelier.card>

                {{-- Riwayat Pembayaran --}}
                <x-atelier.card title="Riwayat Pembayaran" :brackets="true" padding="p-0">
                    @if($ar->allocations->count() > 0)
                        <table class="w-full">
                            <thead class="bg-navy-950/50 border-b border-navy-800">
                                <tr>
                                    <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Payment</th>
                                    <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Tanggal</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-navy-800">
                                @foreach($ar->allocations as $alloc)
                                    <tr>
                                        <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $alloc->payment->payment_number ?? '-' }}</td>
                                        <td class="px-5 py-3 text-sm text-navy-300">{{ $alloc->payment ? format_tanggal($alloc->payment->payment_date) : '-' }}</td>
                                        <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ format_rupiah($alloc->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <x-atelier.empty-state title="Belum ada pembayaran" subtitle="AR ini belum memiliki pembayaran." />
                    @endif
                </x-atelier.card>

                {{-- Jurnal --}}
                <x-atelier.card title="Jurnal Akuntansi" :brackets="true" padding="p-0">
                    @if($ar->journal)
                        <div class="px-5 py-3 border-b border-navy-800 text-sm">
                            <span class="font-mono text-gold-500">{{ $ar->journal->journal_number }}</span>
                            <span class="text-navy-400 ml-3">{{ $ar->journal->description }}</span>
                        </div>
                        <table class="w-full">
                            <thead class="bg-navy-950/50 border-b border-navy-800">
                                <tr>
                                    <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Akun</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Debit</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-navy-800">
                                @foreach($ar->journal->items as $item)
                                    <tr>
                                        <td class="px-5 py-3 text-sm text-white">
                                            <span class="font-mono text-gold-500">{{ $item->account->code }}</span>
                                            {{ $item->account->name }}
                                        </td>
                                        <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ $item->debit > 0 ? format_rupiah($item->debit) : '-' }}</td>
                                        <td class="px-5 py-3 text-sm text-right font-mono text-white">{{ $item->credit > 0 ? format_rupiah($item->credit) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <x-atelier.empty-state title="Belum ada jurnal" subtitle="Jurnal belum dibuat untuk AR ini." />
                    @endif
                </x-atelier.card>
            </div>

            {{-- Summary --}}
            <div class="space-y-6">
                <x-atelier.card title="Ringkasan" :brackets="true">
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Total</p>
                            <p class="font-serif text-2xl font-bold text-white">{{ format_rupiah($ar->total_amount) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Dibayar</p>
                            <p class="font-serif text-xl font-bold text-green-400">{{ format_rupiah($ar->paid_amount) }}</p>
                        </div>
                        <div class="pt-3 border-t border-navy-800">
                            <p class="text-[10px] font-mono text-gold-500 uppercase tracking-widest mb-1">Outstanding</p>
                            <p class="font-serif text-2xl font-bold text-gold-500">{{ format_rupiah($ar->outstanding) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Status</p>
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
                        </div>
                    </div>
                </x-atelier.card>

                <x-atelier.card title="Aksi" :brackets="true">
                    <div class="space-y-2">
                        <x-atelier.button :href="url('finance/ar')" variant="secondary" class="w-full">Kembali ke Daftar AR</x-atelier.button>
                        @if(in_array($ar->status, ['open', 'partial']))
                            <x-atelier.button :href="route('finance.payments.create-in')" variant="primary" class="w-full">Catat Pembayaran</x-atelier.button>
                        @endif
                    </div>
                </x-atelier.card>
            </div>
        </div>
    </div>
</x-app-layout>