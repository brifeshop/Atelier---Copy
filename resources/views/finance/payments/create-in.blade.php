<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Terima Pembayaran</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">
        
        <x-atelier.page-header
            segment="PAY-IN"
            title="Terima Pembayaran Customer"
            subtitle="Catat pelunasan piutang"
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

        {{-- FORM 1: FILTER CUSTOMER (GET) --}}
        <form method="GET" action="{{ route('finance.payments.create-in') }}" class="bg-navy-900/50 border border-navy-800 rounded-xl p-5" x-data="{ 
            selectedCust: '{{ $selectedCustomerId ?? '' }}' 
        }" x-on:change="if(selectedCust != '{{ $selectedCustomerId }}') { $el.submit(); }">
            <label class="text-xs text-navy-400 uppercase tracking-wider block mb-2">Pilih Customer Terlebih Dahulu</label>
            <div class="flex gap-3">
                <select name="customer_id" x-model="selectedCust" class="flex-1 px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500">
                    <option value="">-- Pilih Customer --</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->nama }}</option>
                    @endforeach
                </select>
                <x-atelier.button type="submit" variant="secondary">Tampilkan Tagihan</x-atelier.button>
            </div>
        </form>

        {{-- FORM 2: PEMBAYARAN (POST) --}}
        @if($selectedCustomerId && isset($arInvoices) && $arInvoices->count() > 0)
            <form method="POST" action="{{ route('finance.payments.store-in') }}">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    {{-- Kolom Kiri (Info) --}}
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
                                        <option value="other">Lainnya</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-navy-400 uppercase tracking-wider block mb-1">Akun Kas/Bank</label>
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

                    {{-- Kolom Kanan (Alokasi AR) --}}
                    <div class="lg:col-span-2">
                        <x-atelier.card title="Alokasi Piutang (AR)" :brackets="true">
                            
                            <div class="flex gap-3 items-end mb-3">
                                <div class="flex-1">
                                    <label class="text-[10px] text-navy-400 uppercase block mb-1">Pilih AR</label>
                                    <select name="allocations[0][ar_invoice_id]" required
                                        class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white">
                                        <option value="">-- Pilih AR --</option>
                                        @foreach($arInvoices as $ar)
                                            {{-- TIPS: Kita tidak perlu mengirim customer_id manual, karena backend akan mengambil dari AR yang dipilih --}}
                                            <option value="{{ $ar->id }}">{{ $ar->ar_number }} - Sisa: {{ format_rupiah($ar->outstanding) }}</option>
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
        @elseif($selectedCustomerId)
            {{-- Tampilan jika customer dipilih tapi tidak punya AR --}}
            <x-atelier.card :brackets="true">
                <x-atelier.empty-state title="Tidak ada tagihan" subtitle="Customer ini tidak memiliki AR yang belum lunas." />
            </x-atelier.card>
        @else
            {{-- Tampilan awal sebelum customer dipilih --}}
            <x-atelier.card :brackets="true">
                <x-atelier.empty-state title="Belum ada Customer dipilih" subtitle="Silakan pilih customer terlebih dahulu pada form di atas." />
            </x-atelier.card>
        @endif
    </div>
</x-app-layout>