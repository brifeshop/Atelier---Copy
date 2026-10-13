<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Customer</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">

        <x-atelier.page-header
            segment="M2"
            title="Master Customer"
            subtitle="Daftar semua customer"
            action="Tambah Customer"
            :actionUrl="route('master.customers.create')"
        />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-atelier.card :brackets="true" padding="p-0">
            {{-- Search --}}
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('master.customers.index') }}" class="flex gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari kode, nama, kontak..."
                        class="flex-1 px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                    >
                    <x-atelier.button type="submit" variant="secondary">
                        Cari
                    </x-atelier.button>
                    @if(request('search'))
                        <x-atelier.button :href="route('master.customers.index')" variant="ghost">
                            Reset
                        </x-atelier.button>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            @if($items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <x-sortable-header column="kode" label="Kode" align="left" />
                                <x-sortable-header column="nama" label="Nama" align="left" />
                                <x-sortable-header column="contact_person" label="Kontak" align="left" />
                                <x-sortable-header column="city" label="Kota" align="left" />
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Status</th>
                                <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            @foreach($items as $customer)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-3 text-sm font-mono text-gold-500">{{ $customer->kode }}</td>
                                    <td class="px-5 py-3 text-sm text-white font-medium">{{ $customer->nama }}</td>
                                    <td class="px-5 py-3 text-sm text-navy-300">
                                        {{ $customer->contact_person ?? '-' }}
                                        @if($customer->phone)
                                            <br><span class="text-xs text-navy-500">{{ $customer->phone }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-sm text-navy-300">{{ $customer->city ?? '-' }}</td>
                                    <td class="px-5 py-3">
                                        @if($customer->is_active)
                                            <span class="inline-flex items-center gap-1.5 text-xs text-green-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-xs text-navy-500">
                                                <span class="w-1.5 h-1.5 rounded-full bg-navy-500"></span>
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-atelier.button :href="route('master.customers.show', $customer)" variant="ghost" size="sm">
                                                Lihat
                                            </x-atelier.button>
                                            <x-atelier.button :href="route('master.customers.edit', $customer)" variant="secondary" size="sm">
                                                Edit
                                            </x-atelier.button>
                                            <form method="POST" action="{{ route('master.customers.destroy', $customer) }}" onsubmit="return confirm('Yakin hapus customer ini?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <x-atelier.button type="submit" variant="danger" size="sm">
                                                    Hapus
                                                </x-atelier.button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($items->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">
                        {{ $items->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <x-atelier.empty-state
                    title="Belum ada customer"
                    subtitle="Mulai dengan menambahkan customer pertama."
                    action="Tambah Customer"
                    :actionUrl="route('master.customers.create')"
                />
            @endif
        </x-atelier.card>

    </div>
</x-app-layout>