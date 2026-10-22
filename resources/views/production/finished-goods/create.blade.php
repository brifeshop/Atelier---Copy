<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Buat FG Receipt</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header segment="M5B" title="Buat FG Receipt" subtitle="Terima produk jadi dari produksi" />

        @if($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">
                <p class="font-semibold mb-1">Ada kesalahan:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('production.finished-goods.store') }}" x-data="fgrForm()">
            @csrf

            {{-- INFO DASAR --}}
            <x-atelier.card title="Informasi FG Receipt" :brackets="true">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <x-atelier.input name="fgr_number" label="No. FGR" :value="$generatedNumber" required readonly hint="Auto-generate" />

                    <x-atelier.input name="date" label="Tanggal Terima" type="date" :value="old('date', date('Y-m-d'))" required />

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">
                            Work Order <span class="text-red-400">*</span>
                        </label>
                        <select name="work_order_id" required x-model="workOrderId" @change="onWorkOrderChange()"
                                class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                            <option value="">-- Pilih Work Order --</option>
                            @foreach($workOrders as $wo)
                                <option value="{{ $wo->id }}" 
                                        data-product="{{ $wo->product->nama ?? '-' }}"
                                        data-planned="{{ $wo->planned_qty }}"
                                        data-actual="{{ $wo->actual_qty }}"
                                        data-cost="{{ $wo->cost_per_unit }}"
                                        @selected(old('work_order_id', $workOrder->id ?? '') == $wo->id)>
                                    {{ $wo->wo_number }} — {{ $wo->product->nama ?? '-' }} (Rencana: {{ $wo->planned_qty }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-navy-500 mt-1.5">Hanya WO dengan status In Progress yang muncul.</p>
                    </div>

                    {{-- INFO WO TERPILIH --}}
                    <div x-show="selectedWo" x-cloak class="md:col-span-2 p-3 bg-navy-900/50 border border-navy-800 rounded-lg">
                        <div class="flex flex-wrap gap-x-6 gap-y-2 text-xs">
                            <span class="text-navy-400">Produk: <span class="text-white font-medium" x-text="selectedWo?.product"></span></span>
                            <span class="text-navy-400">Rencana: <span class="text-white" x-text="formatNumber(selectedWo?.planned)"></span></span>
                            <span class="text-navy-400">Selesai: <span class="text-green-400" x-text="formatNumber(selectedWo?.actual)"></span></span>
                            <span class="text-navy-400">Cost/Unit: <span class="text-gold-500" x-text="formatRupiah(selectedWo?.cost)"></span></span>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">
                            Gudang Tujuan <span class="text-red-400">*</span>
                        </label>
                        <select name="warehouse_id" required x-model="warehouseId" @change="loadLocations()"
                                class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                            <option value="">-- Pilih Gudang --</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>
                                    {{ $wh->kode }} — {{ $wh->nama }} ({{ $wh->type }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <x-atelier.input 
                        name="qty_good" 
                        label="Qty Good (Bagus)" 
                        type="number" 
                        step="0.01" 
                        min="0.01" 
                        :value="old('qty_good')" 
                        required 
                        hint="Jumlah produk bagus yang diterima"
                    />

                    <x-atelier.input 
                        name="qty_rejected" 
                        label="Qty Rejected (Reject)" 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        :value="old('qty_rejected', 0)" 
                        hint="Jumlah produk reject (opsional)"
                    />

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">Catatan</label>
                        <textarea name="notes" rows="2" placeholder="Catatan..." class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </x-atelier.card>

            {{-- ITEMS (Multi-Lokasi) --}}
            <x-atelier.card title="Distribusi ke Lokasi" subtitle="Bagi qty ke beberapa lokasi (opsional)" :brackets="true">
                <div x-show="locations.length === 0" class="p-4 bg-navy-800/30 border border-navy-700 rounded-lg text-sm text-navy-400 text-center">
                    Pilih gudang dulu untuk melihat daftar lokasi.
                </div>

                <div x-show="locations.length > 0" x-cloak class="space-y-3">
                    <template x-for="(loc, idx) in itemRows" :key="idx">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end p-3 bg-navy-800/30 border border-navy-700 rounded-lg">
                            <div class="md:col-span-5">
                                <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">Lokasi</label>
                                {{-- Atribut required dihapus agar browser tidak error saat div disembunyikan --}}
                                <select :name="`items[${idx}][location_id]`" x-model="loc.location_id" 
                                        class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded text-sm text-white focus:border-gold-500 focus:outline-none">
                                    <option value="">-- Pilih Lokasi --</option>
                                    <template x-for="l in locations" :key="l.id">
                                        <option :value="l.id" x-text="l.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">Qty</label>
                                {{-- Atribut required dihapus --}}
                                <input type="number" :name="`items[${idx}][qty]`" x-model.number="loc.qty" step="0.01" min="0" 
                                       class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded text-sm text-white focus:border-gold-500 focus:outline-none">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">Catatan</label>
                                <input type="text" :name="`items[${idx}][notes]`" x-model="loc.notes" placeholder="Opsional"
                                       class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:outline-none">
                            </div>
                            <div class="md:col-span-1 text-right">
                                <button type="button" @click="removeRow(idx)" 
                                        x-show="itemRows.length > 1"
                                        class="px-2 py-1.5 text-red-400 hover:text-red-300 text-xs"
                                        title="Hapus baris">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="addRow()" 
                            class="w-full py-2 border-2 border-dashed border-navy-700 rounded-lg text-xs text-navy-400 hover:text-gold-500 hover:border-gold-500/50 transition">
                        + Tambah Lokasi
                    </button>

                    <div class="text-xs text-navy-400 text-right">
                        Total Qty terdistribusi: <span class="text-white font-mono" x-text="formatNumber(totalDistributed)"></span>
                    </div>
                </div>
            </x-atelier.card>

            <div class="flex items-center justify-end gap-3">
                <x-atelier.button :href="route('production.finished-goods.index')" variant="ghost">Batal</x-atelier.button>
                <x-atelier.button type="submit" variant="primary">Buat FG Receipt</x-atelier.button>
            </div>

        </form>
    </div>

    @push('scripts')
    <script>
        function fgrForm() {
            return {
                workOrderId: '{{ old('work_order_id', $workOrder->id ?? '') }}',
                warehouseId: '{{ old('warehouse_id') }}',
                locations: [],
                itemRows: [
                    { location_id: '', qty: '', notes: '' }
                ],

                init() {
                    if (this.warehouseId) {
                        this.loadLocations();
                    }
                },

                get selectedWo() {
                    const select = document.querySelector('select[name="work_order_id"]');
                    const opt = select?.options[select.selectedIndex];
                    if (!opt || !opt.value) return null;
                    return {
                        product: opt.dataset.product,
                        planned: opt.dataset.planned,
                        actual: opt.dataset.actual,
                        cost: opt.dataset.cost,
                    };
                },

                onWorkOrderChange() {
                    const wo = this.selectedWo;
                    if (wo && wo.planned) {
                        const qtyInput = document.querySelector('input[name="qty_good"]');
                        if (qtyInput && !qtyInput.value) {
                            qtyInput.value = wo.actual > 0 ? wo.actual : wo.planned;
                        }
                    }
                },

                async loadLocations() {
                    if (!this.warehouseId) {
                        this.locations = [];
                        return;
                    }

                    try {
                        const url = `{{ url('production/finished-goods/get-locations') }}/${this.warehouseId}`;
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            }
                        });

                        const data = await response.json();
                        // Perbaikan: Cek apakah response berupa array langsung, bukan object { success: true }
                        if (Array.isArray(data)) {
                            // Format data yang diterima dari Laravel
                            this.locations = data.map(l => ({
                                id: l.id,
                                label: `${l.kode} — ${l.nama}`
                            }));
                        } else if (data.success && data.locations) {
                            this.locations = data.locations;
                        }
                    } catch (error) {
                        console.error('Failed to load locations:', error);
                    }
                },

                addRow() {
                    this.itemRows.push({ location_id: '', qty: '', notes: '' });
                },

                removeRow(idx) {
                    this.itemRows.splice(idx, 1);
                },

                get totalDistributed() {
                    return this.itemRows.reduce((sum, r) => sum + (parseFloat(r.qty) || 0), 0);
                },

                formatNumber(value) {
                    if (!value) return '0';
                    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(value);
                },

                formatRupiah(value) {
                    if (!value) return 'Rp 0';
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                },
            }
        }
    </script>
    @endpush

</x-app-layout>