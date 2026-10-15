<x-app-layout>
<x-slot name="header">
<h2 class="font-serif text-2xl font-bold text-white">Tambah Role Baru</h2>
</x-slot>
<div class="p-6 lg:p-8 space-y-6 grid-bg">
     <x-atelier.page-header
         segment="SETTINGS"
         title="Tambah Role"
         subtitle="Buat role baru dan tentukan hak akses permission untuk setiap modul"
     />
     
     {{-- Flash Messages --}}
     @if(session('error'))
         <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
             <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
             </svg>
             <span>{{ session('error') }}</span>
         </div>
     @endif
     
     <form method="POST" action="{{ route('settings.roles.store') }}">
         @csrf
         <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
             {{-- Kolom Kiri: Informasi Role --}}
             <div class="lg:col-span-1 space-y-6">
                 <x-atelier.card title="Informasi Role" subtitle="Identitas dan nama role sistem" :brackets="true">
                     <div class="space-y-4">
                         <x-atelier.input
                             name="name"
                             label="Nama Role"
                             placeholder="cth. Supervisor"
                             :required="true"
                             :value="old('name')"
                         />
                         <div class="pt-4 border-t border-navy-800">
                             <div class="text-xs text-navy-400 space-y-2">
                                 <p class="font-medium text-navy-300">Catatan:</p>
                                 <ul class="list-disc list-inside space-y-1">
                                     <li>Gunakan huruf kapital di awal</li>
                                     <li>Nama role harus unik</li>
                                     <li>Tidak dapat diubah setelah dibuat (future)</li>
                                 </ul>
                             </div>
                         </div>
                     </div>
                 </x-atelier.card>
             </div>
             
             {{-- Kolom Kanan: Permission Checkboxes --}}
             <div class="lg:col-span-2 space-y-6">
                 <x-atelier.card title="Hak Akses Permission" subtitle="Pilih permission untuk setiap modul sistem" :brackets="true">
                     @error('permissions')
                         <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-xs text-red-400">
                             {{ $message }}
                         </div>
                     @enderror
                     
                     <div class="space-y-6">
                         @foreach($permissions as $group => $groupPermissions)
                             <div>
                                 <h3 class="text-sm font-semibold text-gold-400 uppercase tracking-wider mb-3 pb-2 border-b border-navy-800 flex items-center gap-2">
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                     </svg>
                                     Modul {{ ucfirst($group) }}
                                 </h3>
                                 <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                     @foreach($groupPermissions as $permission)
                                         <label class="flex items-start gap-3 p-3 rounded-lg border border-navy-800 bg-navy-950/60 hover:border-gold-500/40 hover:bg-navy-900/60 transition cursor-pointer group">
                                             <input
                                                 type="checkbox"
                                                 name="permissions[]"
                                                 value="{{ $permission->name }}"
                                                 @checked(in_array($permission->name, old('permissions', [])))
                                                 class="mt-0.5 rounded border-navy-700 bg-navy-900 text-gold-500 focus:ring-gold-500/30 w-4 h-4 flex-shrink-0"
                                             >
                                             <div class="flex-1 min-w-0">
                                                 <span class="text-sm text-white font-medium group-hover:text-gold-400 transition">{{ $permission->name }}</span>
                                             </div>
                                         </label>
                                     @endforeach
                                 </div>
                             </div>
                         @endforeach
                     </div>
                     
                     <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-navy-800">
                         <x-atelier.button :href="route('settings.roles.index')" variant="ghost">Batal</x-atelier.button>
                         <x-atelier.button type="submit" variant="primary">Simpan Role</x-atelier.button>
                     </div>
                 </x-atelier.card>
             </div>
         </div>
     </form>
 </div>
</x-app-layout>