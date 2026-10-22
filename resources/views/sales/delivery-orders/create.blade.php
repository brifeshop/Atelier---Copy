<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Buat Delivery Order</h2>
    </x-slot>

    @php
        $salesOrderOptions = $availableSalesOrders->map(fn($so) => [
            'id' => (string) $so->id,
            'label' => $so->so_number . ' — ' . ($so->customer->nama ?? '-') . ' (' . ucfirst($so->status) . ')',
        ])->values()->all();

        $warehouseOptions = $warehouses->map(fn($w) => [
            'id' => (string) $w->id,
            'label' => $w->kode . ' — ' .$w->nama,
        ])->values()->all();
    @endphp

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M9A"
            title="Buat DO"
            subtitle="Buat surat jalan dari Sales Order yang sudah confirmed"
        />

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">
                <p class="font-semibold mb-1">Ada kesalahan:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('sales.delivery-orders.store') }}" x-data="doForm()">
            @csrf

            {{-- =================== INFO DO =================== --}}
            <x-atelier.card title="Informasi Delivery Order" :brackets="true">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- Sales Order searchable (WAJIB) --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">
                            Sales Order <span class="text-red-400">*</span>
                        </label>
                        <div x-data="searchable({
                            items: {{ Js::from($salesOrderOptions) }},
                            selected: '{{ old('sales_order_id') }}',
                            name: 'sales_order_id',
                            onChange: (id) => loadSalesOrder(id)
                        })">
                            <input type="hidden" name="sales_order_id" :value="selected" required>
                            <div x-ref="trigger" @click="toggle()"
                                class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white cursor-pointer flex items-center justify-between"
                                :class="open && 'border-gold-500 ring-2 ring-gold-500/20'">
                                <span x-show="selectedLabel" x-text="selectedLabel" class="truncate"></span>
                                <span x-show="!selectedLabel" class="text-navy-500">-- Pilih Sales Order --</span>
                                <svg class="w-4 h-4 text-navy-500 flex-shrink-0" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                            <template x-teleport="body">
                                <div x-show="open" x-cloak :style="dropdownStyle"
                                    class="bg-navy-800 border border-navy-700 rounded-lg shadow-xl overflow-hidden"
                                    @click.away="open = false">
                                    <div class="p-2 border-b border-navy-700">
                                        <input type="text" x-model="search" :data-search-input="name"
                                            @keydown.arrow-down.prevent="highlightNext()"
                                            @keydown.arrow-up.prevent="highlightPrev()"
                                            @keydown.enter.prevent="selectHighlighted()"
                                            @keydown.escape="open = false"
                                            placeholder="Cari SO..."
                                            class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-0 focus:outline-none">
                                    </div>
                                    <div class="max-h-64 overflow-y-auto">
                                        <template x-for="(item, idx) in filteredItems" :key="item.id">
                                            <div @click="select(item)" @mouseenter="highlighted = idx"
                                                :data-option-index="idx"
                                                class="px-3 py-2 text-sm cursor-pointer"
                                                :class="highlighted === idx ? 'bg-gold-500 text-navy-900' : 'text-white hover:bg-navy-700'"
                                                x-text="item.label"></div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Customer (auto-fill, readonly) --}}
                    <div>
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">Customer</label>
                        <div class="px-3 py-2.5 bg-navy-900 border border-navy-700 rounded-lg text-sm">
                            <span class="text-gold-500 font-mono" x-text="customerName || '-'"></span>
                        </div>
                    </div>

                    {{-- No. DO (auto) --}}
                    <x-atelier.input name="do_number_preview" label="No. DO (auto)" :value="'(otomatis saat simpan)'" readonly />

                    {{-- Tanggal Kirim --}}
                    <x-atelier.input name="delivery_date" label="Tanggal Kirim" type="date" :value="old('delivery_date', date('Y-m-d'))" required />

                    {{-- Gudang Asal searchable --}}
                    <div>
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">
                            Gudang Asal <span class="text-red-400">*</span>
                        </label>
                        <div x-data="searchable({
                            items: {{ Js::from($warehouseOptions) }},
                            selected: '{{ old('warehouse_id') }}',
                            name: 'warehouse_id',
                            onChange: (id) => loadLocations(id)
                        })">
                            <input type="hidden" name="warehouse_id" :value="selected" required>
                            <div x-ref="trigger" @click="toggle()"
                                class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white cursor-pointer flex items-center justify-between"
                                :class="open && 'border-gold-500 ring-2 ring-gold-500/20'">
                                <span x-show="selectedLabel" x-text="selectedLabel" class="truncate"></span>
                                <span x-show="!selectedLabel" class="text-navy-500">-- Pilih Gudang --</span>
                                <svg class="w-4 h-4 text-navy-500 flex-shrink-0" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                            <template x-teleport="body">
                                <div x-show="open" x-cloak :style="dropdownStyle"
                                    class="bg-navy-800 border border-navy-700 rounded-lg shadow-xl overflow-hidden"
                                    @click.away="open = false">
                                    <div class="p-2 border-b border-navy-700">
                                        <input type="text" x-model="search" :data-search-input="name"
                                            @keydown.arrow-down.prevent="highlightNext()"
                                            @keydown.arrow-up.prevent="highlightPrev()"
                                            @keydown.enter.prevent="selectHighlighted()"
                                            @keydown.escape="open = false"
                                            placeholder="Cari gudang..."
                                            class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-0 focus:outline-none">
                                    </div>
                                    <div class="max-h-64 overflow-y-auto">
                                        <template x-for="(item, idx) in filteredItems" :key="item.id">
                                            <div @click="select(item)" @mouseenter="highlighted = idx"
                                                :data-option-index="idx"
                                                class="px-3 py-2 text-sm cursor-pointer"
                                                :class="highlighted === idx ? 'bg-gold-500 text-navy-900' : 'text-white hover:bg-navy-700'"
                                                x-text="item.label"></div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Alamat Kirim --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">Alamat Kirim</label>
                        <textarea name="shipping_address" x-model="shippingAddress" rows="2"
                            class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                            placeholder="Alamat pengiriman..."></textarea>
                    </div>

                    {{-- No. Kendaraan --}}
                    <x-atelier.input name="vehicle_number" label="No. Kendaraan" :value="old('vehicle_number')" placeholder="B 1234 XYZ" />

                    {{-- Nama Driver --}}
                    <x-atelier.input name="driver_name" label="Nama Driver" :value="old('driver_name')" />

                    {{-- Catatan --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">Catatan</label>
                        <textarea name="notes" rows="2"
                            class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                            placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </x-atelier.card>

            {{-- =================== ITEMS =================== --}}
            <x-atelier.card title="Item Pengiriman" subtitle="Pilih lokasi rak asal & sesuaikan qty kirim" :brackets="true">
                <div x-show="loading" class="py-8 text-center">
                    <p class="text-sm text-navy-400">Memuat items...</p>
                </div>

                <div x-show="!loading && items.length === 0" class="p-8 text-center border border-dashed border-navy-700 rounded-lg">
                    <p class="text-sm text-navy-400">Pilih Sales Order dulu untuk memuat items.</p>
                </div>

                <div x-show="!loading && items.length > 0" class="space-y-4">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="bg-navy-800/30 border border-navy-700 rounded-lg p-4">
                            <input type="hidden" :name="`items[${index}][sales_order_item_id]`" :value="item.sales_order_item_id">
                            <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                            <input type="hidden" :name="`items[${index}][qty_ordered]`" :value="item.qty_ordered">

                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <p class="text-sm text-white font-medium" x-text="item.product_nama"></p>
                                    <p class="text-xs font-mono text-navy-500" x-text="item.product_kode"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-navy-400">Sisa SO</p>
                                    <p class="text-sm font-mono text-gold-500" x-text="formatNumber(item.qty_remaining)"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                                {{-- Lokasi Asal (Native Select) --}}
                                <div class="md:col-span-5">
                                    <label class="block text-[10px] font-semibold text-navy-400 uppercase tracking-wider mb-1">Lokasi Asal *</label>
                                    <select :name="`items[${index}][location_id]`" 
                                            x-model="item.location_id" 
                                            @change="checkStock(index)" 
                                            required
                                            class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                                        <option value="">-- Pilih Lokasi --</option>
                                        
                                        <!-- Looping langsung dari locationOptions milik doForm -->
                                        <template x-for="loc in locationOptions" :key="loc.id">
                                            <option :value="loc.id" x-text="loc.label"></option>
                                        </template>
                                    </select>
                                </div>
                                </div>

                                {{-- Qty Kirim --}}
                                <div class="md:col-span-3">
                                    <label class="block text-[10px] font-semibold text-navy-400 uppercase tracking-wider mb-1">Qty Kirim *</label>
                                    <input type="number" :name="`items[${index}][qty_delivered]`"
                                        x-model.number="item.qty_delivered"
                                        @input="checkStock(index)"
                                        step="0.01" min="0.01" :max="item.qty_remaining" required
                                        class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                                    <p x-show="item.stock_error" class="text-xs text-red-400 mt-1" x-text="item.stock_error"></p>
                                    <p x-show="!item.stock_error && item.stock_available !== null" class="text-xs text-navy-500 mt-1">
                                        Stok: <span class="text-green-400" x-text="formatNumber(item.stock_available)"></span>
                                    </p>
                                </div>

                                {{-- Catatan item --}}
                                <div class="md:col-span-3">
                                    <label class="block text-[10px] font-semibold text-navy-400 uppercase tracking-wider mb-1">Catatan Item</label>
                                    <input type="text" :name="`items[${index}][notes]`" x-model="item.notes"
                                        class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                                        placeholder="Opsional">
                                </div>

                                {{-- Tombol Hapus --}}
                                <div class="md:col-span-1 text-right pb-1">
                                    <button type="button" @click="removeItem(index)" title="Batalkan pengiriman barang ini"
                                            class="p-2 text-red-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </x-atelier.card>

            <div class="flex items-center justify-end gap-3">
                <x-atelier.button :href="route('sales.delivery-orders.index')" variant="ghost">Batal</x-atelier.button>
                <x-atelier.button type="submit" variant="primary">Simpan DO</x-atelier.button>
            </div>

        </form>

    </div>

    @push('scripts')
        <script>
            // ===== Searchable component =====
            function searchable({ items, selected, name, onChange = null }) {
                return {
                    get rawItems() {
                        return typeof items === 'function' ? items() : items;
                    },
                    selected: selected || '',
                    search: '',
                    open: false,
                    highlighted: 0,
                    name: name,
                    onChange: onChange,
                    dropdownStyle: '',

                    get filteredItems() {
                        const list = this.rawItems || [];
                        if (!this.search) return list;
                        const q = this.search.toLowerCase();
                        return list.filter(i => i.label && i.label.toLowerCase().includes(q));
                    },

                    get selectedLabel() {
                        const list = this.rawItems || [];
                        const item = list.find(i => String(i.id) === String(this.selected));
                        return item ? item.label : '';
                    },

                    toggle() {
                        this.open = !this.open;
                        if (this.open) {
                            this.search = '';
                            this.highlighted = 0;
                            this.$nextTick(() => {
                                this.updatePosition();
                                setTimeout(() => {
                                    const input = document.querySelector(`[data-search-input="${this.name}"]`);
                                    if (input) input.focus();
                                }, 10);
                            });
                        }
                    },

                    updatePosition() {
                        const trigger = this.$refs.trigger;
                        if (!trigger) return;
                        const rect = trigger.getBoundingClientRect();
                        const dropdownHeight = 320;
                        const spaceBelow = window.innerHeight - rect.bottom;
                        const spaceAbove = rect.top;
                        let top, maxHeight;

                        if (spaceBelow >= dropdownHeight || spaceBelow >= spaceAbove) {
                            top = rect.bottom + 4;
                            maxHeight = Math.min(dropdownHeight, spaceBelow - 20);
                        } else {
                            maxHeight = Math.min(dropdownHeight, spaceAbove - 20);
                            top = rect.top - maxHeight - 4;
                        }

                        this.dropdownStyle = `position: fixed; top: ${top}px; left: ${rect.left}px; width: ${rect.width}px; z-index: 9999;`;
                    },

                    select(item) {
                        this.selected = String(item.id);
                        this.open = false;
                        if (this.onChange) this.onChange(this.selected, item.data || {});
                    },

                    highlightNext() {
                        if (this.highlighted < this.filteredItems.length - 1) this.highlighted++;
                        this.scrollToHighlighted();
                    },
                    highlightPrev() {
                        if (this.highlighted > 0) this.highlighted--;
                        this.scrollToHighlighted();
                    },
                    scrollToHighlighted() {
                        this.$nextTick(() => {
                            const el = document.querySelector(`[data-option-index="${this.highlighted}"]`);
                            if (el) el.scrollIntoView({ block: 'nearest' });
                        });
                    },
                    selectHighlighted() {
                        if (this.filteredItems[this.highlighted]) {
                            this.select(this.filteredItems[this.highlighted]);
                        }
                    },
                }
            }

            // ===== doForm =====
            function doForm() {
                return {
                    items: [],
                    loading: false,
                    customerName: '',
                    shippingAddress: '',
                    locationOptions: [],

                    async loadSalesOrder(soId) {
                        if (!soId) return;

                        this.loading = true;
                        this.items = [];

                        try {
                            const res = await fetch(`/sales/delivery-orders/sales-order/${soId}`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await res.json();

                            this.customerName = data.sales_order.customer;
                            this.shippingAddress = data.sales_order.address;

                            this.items = data.items
                                .filter(i => i.qty_remaining > 0)
                                .map(i => ({
                                    sales_order_item_id: String(i.id),
                                    product_id: String(i.product_id),
                                    product_nama: i.product_nama,
                                    product_kode: i.product_kode,
                                    qty_ordered: i.qty_remaining,
                                    qty_remaining: i.qty_remaining,
                                    qty_delivered: i.qty_remaining,
                                    location_id: '',
                                    notes: '',
                                    stock_available: null,
                                    stock_error: null,
                                }));

                            const warehouseInput = document.querySelector('input[name="warehouse_id"]');
                            if (warehouseInput && warehouseInput.value) {
                                await this.loadLocations(warehouseInput.value);
                            }
                        } catch (e) {
                            alert('Gagal memuat data Sales Order: ' + e.message);
                        } finally {
                            this.loading = false;
                        }
                    },

                    async loadLocations(warehouseId) {
                        this.locationOptions = [];
                        
                        if (!warehouseId) return;

                        try {
                            const url = `{{ url('sales/delivery-orders/warehouse') }}/${warehouseId}/locations`;
                            const res = await fetch(url, {
                                headers: { 
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            
                            if (!res.ok) {
                                throw new Error(`HTTP error! status: ${res.status}`);
                            }
                            
                            const data = await res.json();
                            
                            // Debugging: Munculkan data asli di console browser
                            console.log("Data lokasi dari server:", data);

                            // Jika server mengembalikan response JSON yang formatnya beda
                            let rawLocations = Array.isArray(data) ? data : (data.data || data.locations || []);

                            this.locationOptions = rawLocations.map(l => ({
                                id: String(l.id),
                                label: l.kode + ' — ' + l.nama,
                            }));

                            // Reset lokasi yang sudah dipilih sebelumnya
                            this.items.forEach(i => {
                                i.location_id = '';
                                i.stock_available = null;
                                i.stock_error = null;
                            });
                        } catch (e) {
                            console.error('Gagal load lokasi:', e);
                            alert('Gagal memuat daftar lokasi dari gudang ini. Cek Console (F12) untuk detail error.');
                        }
                    },

                    async checkStock(index) {
                        const item = this.items[index];
                        if (!item.location_id || !item.product_id) return;

                        try {
                            const res = await fetch(`/sales/delivery-orders/check-stock?product_id=${item.product_id}&location_id=${item.location_id}`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await res.json();

                            item.stock_available = data.available;

                            if (item.qty_delivered > data.available) {
                                item.stock_error = `Stok tidak cukup (tersedia ${this.formatNumber(data.available)})`;
                            } else {
                                item.stock_error = null;
                            }
                        } catch (e) {
                            console.error(e);
                        }
                    },

                    removeItem(index) {
                        if (confirm('Batalkan pengiriman untuk barang ini?')) {
                            this.items.splice(index, 1);
                        }
                    },

                    formatNumber(value) {
                        return new Intl.NumberFormat('id-ID').format(Math.round(value || 0));
                    },
                }
            }
        </script>
    @endpush

</x-app-layout>