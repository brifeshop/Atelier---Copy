<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Detail Hutang</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            :segment="$ap->ap_number"
            :title="'AP dari ' . ($ap->goodsReceipt->gr_number ?? '-')"
            :subtitle="$ap->supplier->nama ?? '-'"
        />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-atelier.card title="Informasi" :brackets="true">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">AP Number</p>
                            <p class="font-mono text-gold-500">{{ $ap->ap_number }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">No. Penerimaan</p>
                            <p class="font-mono text-white">{{ $ap->goodsReceipt->gr_number ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Tgl Tagihan</p>
                            <p class="text-white">{{ format_tanggal($ap->bill_date) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Jatuh Tempo</p>
                            <p class="text-white">{{ format_tanggal($ap->due_date) }}</p>
                        </div>
                    </div>
                </x-atelier.card>

                <x-atelier.card title="Jurnal Akuntansi" :brackets="true" padding="p-0">
                    @if($ap->journal)
                        <div class="px-5 py-3 border-b border-navy-800 text-sm">
                            <span class="font-mono text-gold-500">{{ $ap->journal->journal_number }}</span>
                            <span class="text-navy-400 ml-3">{{ $ap->journal->description }}</span>
                        </div>
                        <table class="w-full">
                            <thead class="bg-navy-950/50 border-b border-navy-800">
                                <tr>
                                    <th class="px-5 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Akun</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Debit</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-navy-800">
                                @foreach($ap->journal->items as $item)
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
                        <x-atelier.empty-state title="Belum ada jurnal" subtitle="Jurnal belum dibuat untuk AP ini." />
                    @endif
                </x-atelier.card>
            </div>

            <div class="space-y-6">
                <x-atelier.card title="Ringkasan" :brackets="true">
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Total</p>
                            <p class="font-serif text-2xl font-bold text-white">{{ format_rupiah($ap->total_amount) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Dibayar</p>
                            <p class="font-serif text-xl font-bold text-green-400">{{ format_rupiah($ap->paid_amount) }}</p>
                        </div>
                        <div class="pt-3 border-t border-navy-800">
                            <p class="text-[10px] font-mono text-gold-500 uppercase tracking-widest mb-1">Outstanding</p>
                            <p class="font-serif text-2xl font-bold text-gold-500">{{ format_rupiah($ap->outstanding) }}</p>
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
                            <span class="text-xs px-2 py-1 rounded-full border {{ $colors[$ap->status] ?? '' }}">
                                {{ ucfirst($ap->status) }}
                            </span>
                        </div>
                    </div>
                </x-atelier.card>

                <x-atelier.card title="Aksi" :brackets="true">
                    <div class="space-y-2">
                        @if(in_array($ap->status, ['open', 'partial']))
                            <x-atelier.button :href="route('finance.payments.create-out')" variant="primary" class="w-full">Bayar Hutang Ini</x-atelier.button>
                        @endif
                        
                        <x-atelier.button :href="route('finance.ap.index')" variant="secondary" class="w-full">Kembali ke Daftar AP</x-atelier.button>
                    </div>
                </x-atelier.card>
            </div>
        </div>
    </div>
</x-app-layout>