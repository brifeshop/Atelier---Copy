<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Tambah Work Center Masal</h2>
    </x-slot>

    <x-bulk-input 
        title="Tambah Work Center Masal"
        subtitle="Copy-paste dari Excel untuk input cepat"
        :action="route('master.work-centers.bulk-store')"
        :backUrl="route('master.work-centers.index')"
        :columns="[
            ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'placeholder' => 'WC-CUT-01'],
            ['key' => 'nama', 'label' => 'Nama', 'type' => 'text', 'required' => true, 'placeholder' => 'Cutting Station 1'],
            ['key' => 'location', 'label' => 'Lokasi', 'type' => 'text', 'placeholder' => 'Workshop A'],
            ['key' => 'hourly_rate', 'label' => 'Tarif Labor', 'type' => 'number', 'required' => true, 'placeholder' => '50000'],
            ['key' => 'overhead_rate', 'label' => 'Tarif Overhead', 'type' => 'number', 'placeholder' => '30000'],
            ['key' => 'capacity_per_hour', 'label' => 'Kapasitas/Jam', 'type' => 'number', 'placeholder' => '10'],
            ['key' => 'setup_time_default', 'label' => 'Setup Time', 'type' => 'number', 'placeholder' => '10'],
            ['key' => 'description', 'label' => 'Deskripsi', 'type' => 'text'],
        ]"
        :sample="'WC-CUT-01	Cutting Station 1	Workshop A	50000	30000	10	10	Mesin potong kayu
WC-ASM-01	Assembly Station 1	Workshop A	40000	25000	10	15	Rakit komponen
WC-PNT-01	Painting Station 1	Workshop B	45000	35000	8	20	Finishing cat
WC-INJ-01	Injection Molding 1	Workshop C	100000	50000		Mesin injeksi 100 ton'"
    />

</x-app-layout>