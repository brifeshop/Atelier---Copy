<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Bayar Hutang (Supplier)</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">
        
        <x-atelier.page-header
            segment="PAY-OUT"
            title="Bayar Hutang ke Supplier"
            subtitle="Catat pelunasan hutang usaha"
        />

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm mb-6">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm mb-6">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        {{-- FORM 1: FILTER SUPPLIER --}}
        <form method="GET" action="{{ route('finance.payments.create-out') }}" class="bg-navy-900/50 border border-navy-800 rounded-xl p-5" x-data="{ 
            selectedSupp: '{{ $selectedSupplierId ?? '' }}' 
        }" x-on:change="if(selectedSupp != '{{ $selectedSupplierId }}') { $el.submit(); }">
            <label class="text-xs text-navy-400 uppercase tracking-wider block mb-2">Pilih Supplier Terlebih Dahulu</label>
            <div class="flex gap-3">
                <select name="supplier_id" x-model="selectedSupp" class="flex-1 px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500">
                    <option value="">-- Pilih Supplier --</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->nama }}</option>
                    @endforeach
                </select>
                <x-atelier.button type="submit" variant="secondary">Tampilkan Tagihan</x-atelier.button>
            </div>
        </form>

        {{-- FORM 2: PEMBAYARAN (POST) --}}
        @if($selectedSupplierId && isset($apBills) && $apBills->count() > 0)
            <form method="POST" action="{{ route('finance.payments.store-out') }}">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-1">
                        <x-atelier.card title="Informasi Pembayaran" :brackets="true">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-xs text-navy-400 uppercase tracking-wider block mb-1">Tanggal Bayar</label>
                                    <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required
                                        class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white">
                                </div>
                                <div>
                                    <label class="text-xs text-navy-400 uppercase tracking-wider block mb-1">Metode</label>
                                    <select name="method" required class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white">
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Transfer Bank</option>
                                        <option value="cheque">Cek</option>
                                        <option value="giro">Giro</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-navy-400 uppercase tracking-wider block mb-1">Akun Kas/Bank (Sumber Dana)</label>
                                    <select name="cash_account_code" required class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white">
                                        @foreach($cashAccounts as $acc)
                                            <option value="{{ $acc->code }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-navy-400 uppercase tracking-wider block mb-1">Ref. No (Opsional)</label>
                                    <input type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="No. Transfer / Cek"
                                        class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white">
                                </div>
                                <div>
                                    <label class="text-xs text-navy-400 uppercase tracking-wider block mb-1">Catatan</label>
                                    <textarea name="notes" rows="2" class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </x-atelier.card>
                    </div>

                    <div class="lg:col-span-2">
                        <x-atelier.card title="Alokasi Hutang (AP)" :brackets="true">
                            
                            <div class="flex gap-3 items-end mb-3">
                                <div class="flex-1">
                                    <label class="text-[10px] text-navy-400 uppercase block mb-1">Pilih AP</label>
                                    <select name="allocations[0][ap_bill_id]" required
                                        class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white">
                                        <option value="">-- Pilih AP --</option>
                                        @foreach($apBills as $ap)
                                            <option value="{{ $ap->id }}">{{ $ap->ap_number }} - Sisa: {{ format_rupiah($ap->outstanding) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-48">
                                    <label class="text-[10px] text-navy-400 uppercase block mb-1">Nominal</label>
                                    <input type="number" name="allocations[0][amount]" required step="0.01" min="0.01"
                                        class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white">
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-navy-800 flex justify-end">
                                <x-atelier.button type="submit" variant="primary">Simpan Pembayaran</x-atelier.button>
                            </div>
                        </x-atelier.card>
                    </div>
                </div>
            </form>
        @elseif($selectedSupplierId)
            <x-atelier.card :brackets="true">
                <x-atelier.empty-state title="Tidak ada tagihan" subtitle="Supplier ini tidak memiliki AP yang belum lunas." />
            </x-atelier.card>
        @else
            <x-atelier.card :brackets="true">
                <x-atelier.empty-state title="Belum ada Supplier dipilih" subtitle="Silakan pilih supplier terlebih dahulu pada form di atas." />
            </x-atelier.card>
        @endif
    </div>
</x-app-layout>