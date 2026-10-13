<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Tambah Lokasi Masal</h2>
    </x-slot>

    <x-bulk-input 
        title="Tambah Lokasi Masal"
        subtitle="Copy-paste dari Excel untuk input cepat"
        :action="route('warehouse.locations.bulk-store')"
        :backUrl="route('warehouse.locations.index')"
        :columns="[
            ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'placeholder' => 'INC-R04-LV3'],
            ['key' => 'nama', 'label' => 'Nama Lokasi', 'type' => 'text', 'required' => true],
            ['key' => 'capacity', 'label' => 'Kapasitas', 'type' => 'number'],
            ['key' => 'notes', 'label' => 'Catatan', 'type' => 'text'],
        ]"
        :extraFields="[
            [
                'type' => 'select', 
                'name' => 'warehouse_id', 
                'label' => 'Warehouse', 
                'options' => $warehouses->pluck('nama', 'id'), 
                'required' => true,
            ],
        ]"
        :sample="'INC-R04-LV3	Area Komponen Logam Kecil	100	Rak atas
INC-R04-LV2	Area Fastener	100	Rak tengah
	Area Baut	50	'"
    />

</x-app-layout>