<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Detail QC Inspeksi</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            :segment="$qc->qc_number"
            :title="'QC untuk GR ' . ($qc->goodsReceipt->gr_number ?? '-')"
            :subtitle="$qc->goodsReceipt->supplier->nama ?? '-'"
        />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Kolom Kiri: Form --}}
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('purchasing.qc-inspections.complete', $qc) }}">
                    @csrf
                    @method('PATCH')

                    <x-atelier.card title="Informasi Inspeksi" :brackets="true">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="text-[10px] font-mono text-navy-400 uppercase tracking-widest block mb-1">Metode Inspeksi</label>
                                <select name="inspection_method" required {{ $qc->status !== 'pending' ? 'disabled' : '' }}
                                    class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white">
                                    <option value="100% Check">100% Check</option>
                                    <option value="Sampling">Sampling</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-mono text-navy-400 uppercase tracking-widest block mb-1">Jumlah Sampel</label>
                                <input type="number" name="sample_size" {{ $qc->status !== 'pending' ? 'disabled' : '' }}
                                    class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white" placeholder="0">
                            </div>
                            <div>
                                <label class="text-[10px] font-mono text-navy-400 uppercase tracking-widest block mb-1">Catatan</label>
                                <input type="text" name="notes" {{ $qc->status !== 'pending' ? 'disabled' : '' }}
                                    class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white" placeholder="Opsional">
                            </div>
                        </div>
                    </x-atelier.card>

                    <div class="mt-6">
                        <x-atelier.card title="Item yang Diinspeksi" :brackets="true" padding="p-0">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-navy-950/50 border-b border-navy-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Material</th>
                                            <th class="px-4 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Qty Datang</th>
                                            <th class="px-4 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Qty OK</th>
                                            <th class="px-4 py-3 text-right text-[10px] font-mono text-navy-400 uppercase tracking-widest">Qty Reject</th>
                                            <th class="px-4 py-3 text-left text-[10px] font-mono text-navy-400 uppercase tracking-widest">Alasan Reject</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-navy-800">
                                        @foreach($qc->items as $index => $item)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-white">{{ $item->material->nama ?? '-' }}</td>
                                                <td class="px-4 py-3 text-sm text-right font-mono text-navy-300">{{ number_format($item->qty_received, 2) }}</td>
                                                <td class="px-4 py-3">
                                                    <input type="number" name="items[{{ $index }}][qty_ok]" step="0.01" min="0"
                                                        value="{{ old("items.{$index}.qty_ok", $item->qty_ok) }}"
                                                        {{ $qc->status !== 'pending' ? 'disabled' : '' }}
                                                        class="w-24 px-2 py-1 bg-navy-950 border border-navy-700 rounded text-sm text-white text-right" required>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number" name="items[{{ $index }}][qty_reject]" step="0.01" min="0"
                                                        value="{{ old("items.{$index}.qty_reject", $item->qty_reject) }}"
                                                        {{ $qc->status !== 'pending' ? 'disabled' : '' }}
                                                        class="w-24 px-2 py-1 bg-navy-950 border border-navy-700 rounded text-sm text-white text-right" required>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <select name="items[{{ $index }}][reject_reason]" {{ $qc->status !== 'pending' ? 'disabled' : '' }}
                                                        class="w-full px-2 py-1 bg-navy-950 border border-navy-700 rounded text-sm text-white">
                                                        <option value="">-</option>
                                                        <option value="damaged" @selected($item->reject_reason === 'damaged')>Rusak</option>
                                                        <option value="wrong_spec" @selected($item->reject_reason === 'wrong_spec')>Salah Spesifikasi</option>
                                                        <option value="expired" @selected($item->reject_reason === 'expired')>Kadaluarsa</option>
                                                        <option value="other" @selected($item->reject_reason === 'other')>Lainnya</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </x-atelier.card>
                    </div>

                    @if($qc->status === 'pending')
                        <div class="mt-6 flex justify-end">
                            <x-atelier.button type="submit" variant="primary">Selesaikan Inspeksi</x-atelier.button>
                        </div>
                    @endif
                </form>
            </div>

            {{-- Kolom Kanan: Info --}}
            <div class="space-y-6">
                <x-atelier.card title="Informasi" :brackets="true">
                    <div class="space-y-4 text-sm">
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase mb-1">No. QC</p>
                            <p class="font-mono text-gold-500">{{ $qc->qc_number }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase mb-1">No. GR</p>
                            <p class="font-mono text-white">{{ $qc->goodsReceipt->gr_number ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase mb-1">Tanggal Inspeksi</p>
                            <p class="text-white">{{ format_tanggal($qc->inspection_date) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-mono text-navy-500 uppercase mb-1">Status</p>
                            @php
                                $colors = [
                                    'pending'   => 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
                                    'completed' => 'bg-green-500/10 border-green-500/30 text-green-400',
                                    'rejected'  => 'bg-red-500/10 border-red-500/30 text-red-400',
                                ];
                            @endphp
                            <span class="text-xs px-2 py-1 rounded-full border {{ $colors[$qc->status] ?? '' }}">
                                {{ ucfirst($qc->status) }}
                            </span>
                        </div>
                    </div>
                </x-atelier.card>

                <x-atelier.card title="Aksi" :brackets="true">
                    <div class="space-y-2">
                        <x-atelier.button :href="route('purchasing.qc-inspections.index')" variant="secondary" class="w-full">Kembali ke Daftar</x-atelier.button>
                    </div>
                </x-atelier.card>
            </div>
        </div>
    </div>
</x-app-layout>