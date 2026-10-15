<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">System Profile</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">
        <x-atelier.page-header
            segment="SETTINGS"
            title="Profil Perusahaan"
            subtitle="Kelola identitas bisnis, alamat legal, dan branding sistem"
        />

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('settings.system.profile.update') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {{-- Kolom Kiri: Logo Preview --}}
                <div class="lg:col-span-1 space-y-6">
                    <x-atelier.card title="Logo Perusahaan" subtitle="Format JPG/PNG, maks 2MB" :brackets="true">
                        <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-navy-700 rounded-xl bg-navy-950/50 hover:border-gold-500/40 transition group">
                            @if(!empty($data['logo_path']))
                                <img src="{{ asset('storage/' . $data['logo_path']) }}" alt="Current Logo" class="h-32 w-auto object-contain mb-4 drop-shadow-lg">
                            @else
                                <div class="h-32 w-32 rounded-full bg-navy-800 flex items-center justify-center mb-4 group-hover:bg-navy-700 transition">
                                    <svg class="w-12 h-12 text-navy-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                            
                            <label class="cursor-pointer">
                                <span class="px-4 py-2 bg-navy-800 text-sm text-white rounded-lg hover:bg-gold-500 hover:text-navy-900 transition font-medium">Pilih Logo Baru</span>
                                <input type="file" name="logo" accept="image/*" class="hidden" onchange="this.form.submit()">
                            </label>
                            <p class="text-[10px] text-navy-500 mt-3 text-center">Upload akan langsung menyimpan perubahan</p>
                        </div>
                    </x-atelier.card>
                </div>

                {{-- Kolom Kanan: Form Identitas --}}
                <div class="lg:col-span-2 space-y-6">
                    <x-atelier.card title="Informasi Legal & Kontak" subtitle="Data ini akan tampil pada Invoice, Surat Jalan, dan Footer Sistem" :brackets="true">
                        <div class="space-y-4">
                            <x-atelier.input name="company_name" label="Nama Perusahaan" :value="$data['company_name']" :required="true" />
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <x-atelier.input name="company_npwp" label="NPWP / Tax ID" :value="$data['company_npwp']" placeholder="00.000.000.0-000.000" />
                                <x-atelier.input name="company_phone" label="Telepon Kantor" :value="$data['company_phone']" type="tel" />
                            </div>

                            <x-atelier.input name="company_email" label="Email Resmi" :value="$data['company_email']" type="email" />
                            
                            <div>
                                <label class="block text-sm font-medium text-navy-300 mb-2">Alamat Lengkap</label>
                                <textarea name="company_address" rows="3" 
                                    class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">{{ old('company_address', $data['company_address']) }}</textarea>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-navy-800">
                            <x-atelier.button :href="route('dashboard')" variant="ghost">Batal</x-atelier.button>
                            <x-atelier.button type="submit" variant="primary">Simpan Profil</x-atelier.button>
                        </div>
                    </x-atelier.card>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>