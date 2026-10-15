<x-app-layout>
<x-slot name="header">
<h2 class="font-serif text-2xl font-bold text-white">Detail Role</h2>
</x-slot>
<div class="p-6 lg:p-8 space-y-6 grid-bg">
     <x-atelier.page-header
         segment="SETTINGS"
         title="Detail Role"
         subtitle="Informasi lengkap role dan hak akses permission"
     />
     
     <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
         {{-- Kolom Kiri: Info Role --}}
         <div class="space-y-6">
             <x-atelier.card title="Informasi Role" :brackets="true">
                 @php
                     $badgeClass = match($role->name) {
                         'Owner'      => 'bg-gold-500/10 text-gold-400 border-gold-500/30',
                         'Admin'      => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                         'Manager'    => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                         'Sales'      => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                         'PPIC'       => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30',
                         'Purchasing' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                         'Warehouse'  => 'bg-orange-500/10 text-orange-400 border-orange-500/30',
                         'Produksi'   => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30',
                         'QC'         => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                         'Finance'    => 'bg-teal-500/10 text-teal-400 border-teal-500/30',
                         default      => 'bg-navy-800 text-navy-300 border-navy-700',
                     };
                 @endphp
                 <div class="space-y-4">
                     <div>
                         <span class="text-xs text-navy-400 uppercase tracking-wider">Nama Role</span>
                         <div class="mt-1">
                             <span class="inline-flex items-center px-3 py-1.5 rounded text-base font-semibold border {{ $badgeClass }}">
                                 {{ $role->name }}
                             </span>
                         </div>
                     </div>
                     <div class="pt-4 border-t border-navy-800">
                         <div class="grid grid-cols-2 gap-4">
                             <div>
                                 <span class="text-xs text-navy-400 uppercase tracking-wider">Total Permission</span>
                                 <p class="mt-1 text-lg font-semibold text-white">{{ $role->permissions_count }}</p>
                             </div>
                             <div>
                                 <span class="text-xs text-navy-400 uppercase tracking-wider">Total User</span>
                                 <p class="mt-1 text-lg font-semibold text-white">{{ $role->users_count }}</p>
                             </div>
                         </div>
                     </div>
                 </div>
             </x-atelier.card>
             
             {{-- Action Buttons --}}
             <x-atelier.card :brackets="true">
                 <div class="space-y-3">
                     <x-atelier.button :href="route('settings.roles.edit', $role)" variant="primary" class="w-full justify-center">
                         <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                         </svg>
                         Edit Role
                     </x-atelier.button>
                     @if(strtolower($role->name) !== 'owner')
                         <form method="POST" action="{{ route('settings.roles.destroy', $role) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus role ini?');">
                             @csrf
                             @method('DELETE')
                             <button type="submit" class="w-full px-4 py-2 bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg hover:bg-red-500/20 transition text-sm font-medium flex items-center justify-center">
                                 <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                 </svg>
                                 Hapus Role
                             </button>
                         </form>
                     @endif
                     <x-atelier.button :href="route('settings.roles.index')" variant="ghost" class="w-full justify-center">Kembali</x-atelier.button>
                 </div>
             </x-atelier.card>
         </div>
         
         {{-- Kolom Kanan: Permissions & Users --}}
         <div class="lg:col-span-2 space-y-6">
             {{-- List Permissions --}}
             <x-atelier.card title="Daftar Permission" subtitle="Hak akses yang dimiliki role ini" :brackets="true">
                 @if($role->permissions->count() > 0)
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                         @foreach($role->permissions->sortBy('name') as $permission)
                             @php
                                 $group = explode('.', $permission->name)[0] ?? 'General';
                             @endphp
                             <div class="flex items-center gap-3 p-3 rounded-lg bg-navy-950/60 border border-navy-800">
                                 <svg class="w-4 h-4 text-gold-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                 </svg>
                                 <div class="flex-1 min-w-0">
                                     <span class="text-sm text-white font-medium">{{ $permission->name }}</span>
                                     <span class="text-xs text-navy-500 ml-2">({{ ucfirst($group) }})</span>
                                 </div>
                             </div>
                         @endforeach
                     </div>
                 @else
                     <x-atelier.empty-state
                         title="Tidak Ada Permission"
                         message="Role ini belum memiliki permission. Silakan edit role untuk menambahkan permission."
                     />
                 @endif
             </x-atelier.card>
             
             {{-- List Users --}}
             <x-atelier.card title="Pengguna dengan Role Ini" subtitle="Daftar user yang menggunakan role {{ $role->name }}" :brackets="true">
                 @if($role->users->count() > 0)
                     <div class="space-y-2">
                         @foreach($role->users->take(10) as $user)
                             <div class="flex items-center justify-between p-3 rounded-lg bg-navy-950/60 border border-navy-800 hover:border-navy-700 transition">
                                 <div class="flex items-center gap-3">
                                     <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gold-500/20 to-navy-800 border border-gold-500/30 flex items-center justify-center font-bold text-xs text-gold-400 flex-shrink-0">
                                         {{ strtoupper(substr($user->name, 0, 1)) }}
                                     </div>
                                     <div>
                                         <p class="text-sm font-medium text-white">{{ $user->name }}</p>
                                         <p class="text-xs text-navy-400 font-mono">{{ $user->email }}</p>
                                     </div>
                                 </div>
                                 <a href="{{ route('settings.users.show', $user) }}" class="text-xs text-gold-400 hover:text-gold-300 transition">
                                     Detail →
                                 </a>
                             </div>
                         @endforeach
                         @if($role->users->count() > 10)
                             <div class="pt-3 mt-3 border-t border-navy-800 text-center">
                                 <span class="text-xs text-navy-400">Dan {{ $role->users->count() - 10 }} user lainnya</span>
                             </div>
                         @endif
                     </div>
                 @else
                     <x-atelier.empty-state
                         title="Tidak Ada Pengguna"
                         message="Belum ada user yang menggunakan role ini."
                     />
                 @endif
             </x-atelier.card>
         </div>
     </div>
 </div>
</x-app-layout>