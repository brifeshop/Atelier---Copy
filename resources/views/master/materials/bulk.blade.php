<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Tambah Material Masal</h2>
    </x-slot>

    <x-bulk-input 
        title="Tambah Material Masal"
        subtitle="Copy-paste dari Excel untuk input cepat"
        :action="route('master.materials.bulk-store')"
        :backUrl="route('master.materials.index')"
        :columns="[
            ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'placeholder' => 'MAT-0001'],
            ['key' => 'kode_bahan', 'label' => 'Kode Internal', 'type' => 'text', 'placeholder' => 'A8-18-0'],
            ['key' => 'nama', 'label' => 'Nama Material', 'type' => 'text', 'required' => true, 'placeholder' => 'Kayu Pinus'],
            ['key' => 'spesifikasi', 'label' => 'Spesifikasi', 'type' => 'text', 'placeholder' => '164 x 120 x 18 mm'],
            ['key' => 'category', 'label' => 'Kategori', 'type' => 'text', 'placeholder' => 'Kayu'],
            ['key' => 'unit', 'label' => 'Satuan Beli', 'type' => 'text', 'required' => true, 'placeholder' => 'pcs'],
            ['key' => 'costing_method', 'label' => 'Costing Method', 'type' => 'text', 'required' => true, 'placeholder' => 'per_unit'],
            ['key' => 'panjang_standar', 'label' => 'Panjang (mm)', 'type' => 'number', 'placeholder' => '2400'],
            ['key' => 'lebar_standar', 'label' => 'Lebar (mm)', 'type' => 'number', 'placeholder' => '1200'],
            ['key' => 'tinggi_standar', 'label' => 'Tinggi (mm)', 'type' => 'number', 'placeholder' => '18'],
            ['key' => 'berat_standar', 'label' => 'Berat (gram)', 'type' => 'number', 'placeholder' => '25000'],
            ['key' => 'volume_standar', 'label' => 'Volume (ml)', 'type' => 'number', 'placeholder' => '5000'],
            ['key' => 'volume_type', 'label' => 'Volume Type', 'type' => 'text', 'placeholder' => 'kotak / cair'],
            ['key' => 'price', 'label' => 'Harga', 'type' => 'number', 'placeholder' => '100000'],
            ['key' => 'yield_percent', 'label' => 'Yield %', 'type' => 'number', 'placeholder' => '100'],
            ['key' => 'min_stock', 'label' => 'Min Stock', 'type' => 'number', 'placeholder' => '10'],
            ['key' => 'max_stock', 'label' => 'Max Stock', 'type' => 'number', 'placeholder' => '100'],
            ['key' => 'location', 'label' => 'Lokasi', 'type' => 'text', 'placeholder' => 'Rak A-01'],
        ]"
        :sample="'MAT-0001	A8-18-0	Kayu Pinus	164 x 120 x 18 mm	Kayu	pcs	per_unit							100000	100	10	100	Rak A-01
	A8-19-0	Kayu Pinus 150x100	150 x 100 x 18 mm	Kayu	pcs	per_unit							80000	100	10	100	Rak A-01
	F5-2-1	Varnish Doff 714D	Doff 714D	Finishing	kaleng	per_volume	cair				5000	5000	135000	90	5	50	Rak B-01
	A7-5-0	Dowel Pinus dia.20 mm	dia.20 mm	Kayu	batang	per_length		1000				10500	95	5	50	Rak A-02'"
    />

</x-app-layout>