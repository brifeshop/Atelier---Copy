<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">System Preferences</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">
        <x-atelier.page-header
            segment="SETTINGS"
            title="Preferensi Global"
            subtitle="Atur standar perhitungan, format tampilan, dan zona waktu sistem"
        />

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('settings.system.preferences.update') }}">
            @csrf
            <div class="max-w-3xl">
                <x-atelier.card title="Standarisasi Bisnis" :brackets="true">
                    <div class="space-y-5">
                        
                        {{-- Mata Uang --}}
                        <div>
                            <label class="block text-sm font-medium text-navy-300 mb-2">Mata Uang Default</label>
                            <select name="currency" class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                                <option value="IDR" @selected($data['currency'] == 'IDR')>IDR - Rupiah Indonesia</option>
                                <option value="USD" @selected($data['currency'] == 'USD')>USD - US Dollar</option>
                                <option value="EUR" @selected($data['currency'] == 'EUR')>EUR - Euro</option>
                                <option value="SGD" @selected($data['currency'] == 'SGD')>SGD - Singapore Dollar</option>
                            </select>
                            <p class="text-xs text-navy-500 mt-1">Berlaku untuk semua modul Sales, Purchasing, dan Finance.</p>
                        </div>

                        {{-- Pajak --}}
                        <div>
                            <label class="block text-sm font-medium text-navy-300 mb-2">Tarif Pajak Default (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $data['tax_rate']) }}" 
                                class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                            <p class="text-xs text-navy-500 mt-1">Digunakan sebagai default saat membuat Invoice atau PO baru.</p>
                        </div>

                        {{-- Format Tanggal --}}
                        <div>
                            <label class="block text-sm font-medium text-navy-300 mb-2">Format Tampilan Tanggal</label>
                            <select name="date_format" class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                                <option value="DD/MM/YYYY" @selected($data['date_format'] == 'DD/MM/YYYY')>DD/MM/YYYY (Contoh: 15/10/2026)</option>
                                <option value="MM/DD/YYYY" @selected($data['date_format'] == 'MM/DD/YYYY')>MM/DD/YYYY (Contoh: 10/15/2026)</option>
                                <option value="YYYY-MM-DD" @selected($data['date_format'] == 'YYYY-MM-DD')>YYYY-MM-DD (Contoh: 2026-10-15)</option>
                            </select>
                        </div>

                        {{-- Zona Waktu --}}
                        <div>
                            <label class="block text-sm font-medium text-navy-300 mb-2">Zona Waktu Server</label>
                            <select name="timezone" class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                                <option value="Asia/Jakarta" @selected($data['timezone'] == 'Asia/Jakarta')>WIB - Asia/Jakarta</option>
                                <option value="Asia/Makassar" @selected($data['timezone'] == 'Asia/Makassar')>WITA - Asia/Makassar</option>
                                <option value="Asia/Jayapura" @selected($data['timezone'] == 'Asia/Jayapura')>WIT - Asia/Jayapura</option>
                                <option value="UTC" @selected($data['timezone'] == 'UTC')>UTC - Coordinated Universal Time</option>
                            </select>
                        </div>

                    </div>

                    <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-navy-800">
                        <x-atelier.button :href="route('dashboard')" variant="ghost">Batal</x-atelier.button>
                        <x-atelier.button type="submit" variant="primary">Simpan Preferensi</x-atelier.button>
                    </div>
                </x-atelier.card>
            </div>
        </form>
    </div>
</x-app-layout>