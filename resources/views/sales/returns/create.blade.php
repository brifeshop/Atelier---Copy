<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Buat Sales Return</h2>
    </x-slot>

    @php
        $doOptions = $availableDeliveryOrders->map(fn($do) => [
            'id' => (string) $do->id,
            'label' => $do->do_number . ' — ' . ($do->customer->nama ?? '-'),
        ])->values()->all();

        $locationOptions = $locations->map(fn($loc) => [
            'id' => (string) $loc->id,
            'label' => $loc->kode . ' — ' . $loc->nama . ' (' . ($loc->warehouse->nama ?? '-') . ')',
        ])->values()->all();
    @endphp

    <div class="p-6 lg:p-8 space-y-6 grid-bg">
        <x-atelier.page-header segment="M9C" title="Buat Retur" subtitle="Catat pengembalian barang dari customer" />

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

        <form method="POST" action="{{ route('sales.returns.store') }}" x-data="returnForm()">
            @csrf

            <x-atelier.card title="Informasi Retur" :brackets="true">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Pilih Delivery Order --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">
                            Delivery Order <span class="text-red-400">*</span>
                        </label>
                        <div x-data="searchable({
                            items: {{ Js::from($doOptions) }},
                            selected: '{{ old('delivery_order_id', $selectedDoId ?? '') }}',
                            name: 'delivery_order_id',
                            onChange: (id) => loadDO(id)
                        })">
                            <input type="hidden" name="delivery_order_id" :value="selected" required>
                            <div x-ref="trigger" @click="toggle()" class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white cursor-pointer flex items-center justify-between"
                                :class="open && 'border-gold-500 ring-2 ring-gold-500/20'">
                                <span x-show="selectedLabel" x-text="selectedLabel" class="truncate"></span>
                                <span x-show="!selectedLabel" class="text-navy-500">-- Pilih Delivery Order --</span>
                                <svg class="w-4 h-4 text-navy-500 flex-shrink-0" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                            <template x-teleport="body">
                                <div x-show="open" x-cloak :style="dropdownStyle" class="bg-navy-800 border border-navy-700 rounded-lg shadow-xl overflow-hidden" @click.away="open = false">
                                    <div class="p-2 border-b border-navy-700">
                                        <input type="text" x-model="search" :data-search-input="name" placeholder="Cari DO..."
                                            class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-0 focus:outline-none">
                                    </div>
                                    <div class="max-h-64 overflow-y-auto">
                                        <template x-for="(item, idx) in filteredItems" :key="item.id">
                                            <div @click="select(item)" @mouseenter="highlighted = idx"
                                                class="px-3 py-2 text-sm cursor-pointer"
                                                :class="highlighted === idx ? 'bg-gold-500 text-navy-900' : 'text-white hover:bg-navy-700'"
                                                x-text="item.label"></div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">Customer</label>
                        <div class="px-3 py-2.5 bg-navy-900 border border-navy-700 rounded-lg text-sm">
                            <span class="text-gold-500 font-mono" x-text="customerName || '-'"></span>
                        </div>
                    </div>

                    <x-atelier.input name="return_date" label="Tanggal Retur" type="date" :value="old('return_date', date('Y-m-d'))" required />

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">
                            Alasan Retur <span class="text-red-400">*</span>
                        </label>
                        <select name="reason" required class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                            <option value="">-- Pilih Alasan --</option>
                            <option value="damaged" @selected(old('reason') == 'damaged')>Barang Rusak</option>
                            <option value="wrong_item" @selected(old('reason') == 'wrong_item')>Salah Kirim Barang</option>
                            <option value="expired" @selected(old('reason') == 'expired')>Kadaluarsa</option>
                            <option value="other" @selected(old('reason') == 'other')>Lainnya</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">Catatan</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:outline-none" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </x-atelier.card>

            {{-- Items --}}
            <x-atelier.card title="Item yang Dikembalikan" :brackets="true">
                <div x-show="loading" class="py-8 text-center">
                    <p class="text-sm text-navy-400">Memuat item dari DO...</p>
                </div>

                <div x-show="!loading && items.length === 0" class="p-8 text-center border border-dashed border-navy-700 rounded-lg">
                    <p class="text-sm text-navy-400">Pilih Delivery Order terlebih dahulu.</p>
                </div>

                <div x-show="!loading && items.length > 0" class="space-y-4">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="bg-navy-800/30 border border-navy-700 rounded-lg p-4">
                            <input type="hidden" :name="`items[${index}][delivery_order_item_id]`" :value="item.delivery_order_item_id">
                            <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                            <input type="hidden" :name="`items[${index}][unit_price]`" :value="item.unit_price">

                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <p class="text-sm text-white font-medium" x-text="item.product_nama"></p>
                                    <p class="text-xs font-mono text-navy-500" x-text="item.product_kode"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-navy-400">Qty Terkirim</p>
                                    <p class="text-sm font-mono text-gold-500" x-text="formatNumber(item.qty_shipped)"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                                <div class="md:col-span-4">
                                    <label class="block text-[10px] font-semibold text-navy-400 uppercase tracking-wider mb-1">Qty Retur *</label>
                                    <input type="number" :name="`items[${index}][qty]`" x-model.number="item.qty" step="0.01" min="0.01" :max="item.qty_shipped" required
                                        class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                                </div>

                                <div class="md:col-span-5">
                                    <label class="block text-[10px] font-semibold text-navy-400 uppercase tracking-wider mb-1">Lokasi Tujuan Retur *</label>
                                    <select :name="`items[${index}][location_id]`" x-model="item.location_id" required class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                                        <option value="">-- Pilih Lokasi --</option>
                                        <template x-for="loc in locationOptions" :key="loc.id">
                                            <option :value="loc.id" x-text="loc.label"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-[10px] font-semibold text-navy-400 uppercase tracking-wider mb-1">Catatan</label>
                                    <input type="text" :name="`items[${index}][notes]`" x-model="item.notes" class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:outline-none">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </x-atelier.card>

            <div class="flex items-center justify-end gap-3">
                <x-atelier.button :href="route('sales.returns.index')" variant="ghost">Batal</x-atelier.button>
                <x-atelier.button type="submit" variant="primary">Simpan Retur</x-atelier.button>
            </div>
        </form>
    </div>

        @push('scripts')
    <script>
        function searchable({ items, selected, name, onChange = null }) {
            return {
                items: items,
                selected: selected || '',
                search: '',
                open: false,
                highlighted: 0,
                name: name,
                onChange: onChange,
                dropdownStyle: '',

                get filteredItems() {
                    if (!this.search) return this.items;
                    const q = this.search.toLowerCase();
                    return this.items.filter(i => i.label.toLowerCase().includes(q));
                },

                get selectedLabel() {
                    const item = this.items.find(i => i.id === this.selected);
                    return item ? item.label : '';
                },

                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.search = '';
                        this.highlighted = 0;
                        this.$nextTick(() => {
                            const rect = this.$refs.trigger.getBoundingClientRect();
                            this.dropdownStyle = `position: fixed; top: ${rect.bottom + 4}px; left: ${rect.left}px; width: ${rect.width}px; z-index: 9999;`;
                        });
                    }
                },

                select(item) {
                    this.selected = item.id;
                    this.open = false;
                    if (this.onChange) this.onChange(item.id);
                }
            }
        }

        function returnForm() {
            return {
                items: [],
                loading: false,
                customerName: '',
                locationOptions: {{ Js::from($locationOptions) }},

                // Tambahkan init() agar saat halaman dibuka dari tombol DO, data langsung ditarik
                init() {
                    const hiddenInput = document.querySelector('input[name="delivery_order_id"]');
                    if (hiddenInput && hiddenInput.value) {
                        this.loadDO(hiddenInput.value);
                    }
                },

                async loadDO(doId) {
                    if (!doId) return;
                    this.loading = true;
                    this.items = [];

                    try {
                        const res = await fetch(`{{ url('sales/returns/delivery-order') }}/${doId}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();

                        this.customerName = data.delivery_order.customer;

                        this.items = data.items
                            .filter(i => i.qty_shipped > 0)
                            .map(i => ({
                                delivery_order_item_id: String(i.id),
                                product_id: String(i.product_id),
                                product_nama: i.product_nama,
                                product_kode: i.product_kode,
                                qty_shipped: i.qty_shipped,
                                qty: i.qty_shipped,
                                unit_price: i.unit_price,
                                location_id: '',
                                notes: '',
                            }));
                    } catch (e) {
                        alert('Gagal memuat data DO: ' + e.message);
                    } finally {
                        this.loading = false;
                    }
                },

                formatNumber(value) {
                    return new Intl.NumberFormat('id-ID').format(Math.round(value || 0));
                }
            }
        }
    </script>
    @endpush
</x-app-layout>