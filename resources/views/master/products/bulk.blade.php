<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Tambah Product Masal</h2>
    </x-slot>

    <x-bulk-input 
        title="Tambah Product Masal"
        subtitle="Copy-paste dari Excel untuk input cepat"
        :action="route('master.products.bulk-store')"
        :backUrl="route('master.products.index')"
        :columns="[
            ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'placeholder' => 'PRD-0001'],
            ['key' => 'nama', 'label' => 'Nama Produk', 'type' => 'text', 'required' => true, 'placeholder' => 'Meja Kayu Sederhana'],
            ['key' => 'category', 'label' => 'Kategori', 'type' => 'text', 'placeholder' => 'Furnitur'],
            ['key' => 'unit', 'label' => 'Satuan', 'type' => 'text', 'required' => true, 'placeholder' => 'unit'],
            ['key' => 'selling_price', 'label' => 'Harga Jual', 'type' => 'number', 'placeholder' => '500000'],
            ['key' => 'description', 'label' => 'Deskripsi', 'type' => 'text'],
        ]"
        :sample="'PRD-001	Meja Kayu Sederhana	Furnitur	unit	500000	Meja minimalis
PRD-002	Kursi Kayu	Furnitur	unit	250000	Kursi tanpa sandaran
PRD-003	Lemari Pakaian	Furnitur	unit	1500000	Lemari 2 pintu'"
    />

</x-app-layout>