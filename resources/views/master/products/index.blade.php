<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Product</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <div class="flex items-start justify-between flex-wrap gap-4">
            <x-atelier.page-header
                segment="M2"
                title="Master Product"
                subtitle="Daftar semua produk jadi"
            />
            <div class="flex gap-2">
                <x-atelier.button :href="route('master.products.bulk')" variant="secondary">
                    📋 Input Masal
                </x-atelier.button>
                <x-atelier.button :href="route('master.products.create')" variant="primary">
                    + Tambah Product
                </x-atelier.button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('master.products.index') }}" class="flex gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari kode, nama, kategori..."
                        class="flex-1 px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                    >
                    <x-atelier.button type="submit" variant="secondary">Cari</x-atelier.button>
                    @if(request('search'))
                        <x-atelier.button :href="route('master.products.index')" variant="ghost">Reset</x-atelier.button>
                    @endif
                </form>
            </div>

            @if($items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest w-16">Foto</th>
                                <x-sortable-header column="kode" label="Kode" align="left" />
                                <x-sortable-header column="nama" label="Nama" align="left" />
                                <x-sortable-header column="category" label="Kategori" align="left" />
                                <x-sortable-header column="unit" label="Satuan" align="left" />
                                <x-sortable-header column="selling_price" label="Harga Jual" align="right" />
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Status</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($items as $product)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3">
                                        @if($product->photo_url)
                                            <img src="{{ $product->photo_url }}"
                                                 alt="{{ $product->nama }}"
                                                 class="w-12 h-12 object-cover rounded-lg border border-navy-700">
                                        @else
                                            <div class="w-12 h-12 rounded-lg border border-navy-700 bg-navy-900 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-navy-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $product->kode }}</td>
                                    <td class="px-5 py-3 text-sm text-white font-medium">{{ $product->nama }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ $product->category ?? '-' }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ $product->unit }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono text-white">
                                        {{ format_rupiah($product->selling_price) }}
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($product->is_active)
                                            <span class="inline-flex items-center gap-1.5 text-xs text-green-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-xs text-navy-500">
                                                <span class="w-1.5 h-1.5 rounded-full bg-navy-500"></span>Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-atelier.button :href="route('master.products.show', $product)" variant="ghost" size="sm">Lihat</x-atelier.button>
                                            <x-atelier.button :href="route('master.products.edit', $product)" variant="secondary" size="sm">Edit</x-atelier.button>
                                            <form method="POST" action="{{ route('master.products.destroy', $product) }}" onsubmit="return confirm('Yakin hapus product ini?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <x-atelier.button type="submit" variant="danger" size="sm">Hapus</x-atelier.button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($items->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">
                        {{ $items->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <x-atelier.empty-state
                    title="Belum ada product"
                    subtitle="Mulai dengan menambahkan product pertama."
                    action="Tambah Product"
                    :actionUrl="route('master.products.create')"
                />
            @endif
        </x-atelier.card>

    </div>
</x-app-layout>