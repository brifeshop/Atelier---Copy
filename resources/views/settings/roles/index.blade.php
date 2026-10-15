<x-app-layout>
<x-slot name="header">
<h2 class="font-serif text-2xl font-bold text-white">Role Management</h2>
</x-slot>
<div class="p-6 lg:p-8 space-y-6 grid-bg">
     <x-atelier.page-header
         segment="SETTINGS"
         title="Role Management"
         subtitle="Kelola role sistem dan hak akses permission"
         action="Tambah Role"
         :actionUrl="route('settings.roles.create')"
     />
     
     {{-- Flash Messages --}}
     @if(session('success'))
         <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm flex items-center justify-between">
             <div class="flex items-center gap-2">
                 <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                 </svg>
                 <span>{{ session('success') }}</span>
             </div>
         </div>
     @endif
     @if(session('error'))
         <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm flex items-center justify-between">
             <div class="flex items-center gap-2">
                 <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                 </svg>
                 <span>{{ session('error') }}</span>
             </div>
         </div>
     @endif
     
     {{-- Card Search & Filter --}}
     <x-atelier.card :brackets="true" padding="p-0">
         <div class="px-5 py-4 border-b border-navy-800">
             <form method="GET" action="{{ route('settings.roles.index') }}" class="flex flex-wrap items-center gap-3">
                 <div class="flex-1 min-w-[200px]">
                     <input
                         type="text"
                         name="search"
                         value="{{ request('search') }}"
                         placeholder="Cari nama role..."
                         class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
                     >
                 </div>
                 <x-atelier.button type="submit" variant="secondary">Filter</x-atelier.button>
                 @if(request()->hasAny(['search']))
                     <x-atelier.button :href="route('settings.roles.index')" variant="ghost">Reset</x-atelier.button>
                 @endif
             </form>
         </div>
         
         @if($roles->count() > 0)
             <div class="overflow-x-auto">
                 <table class="w-full text-left">
                     <thead class="bg-navy-950/60 border-b border-navy-800">
                         <tr>
                             <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Nama Role</th>
                             <th class="px-5 py-3 text-center text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Permission</th>
                             <th class="px-5 py-3 text-center text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">User</th>
                             <th class="px-5 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aksi</th>
                         </tr>
                     </thead>
                     <tbody class="divide-y divide-navy-800/80">
                         @foreach($roles as $role)
                             <tr class="hover:bg-navy-800/30 transition">
                                 {{-- Kolom Nama Role --}}
                                 <td class="px-5 py-4">
                                     <div class="flex items-center gap-3">
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
                                         <span class="inline-flex items-center px-3 py-1 rounded text-sm font-semibold border {{ $badgeClass }}">
                                             {{ $role->name }}
                                         </span>
                                     </div>
                                 </td>
                                 {{-- Kolom Jumlah Permission --}}
                                 <td class="px-5 py-4 text-center">
                                     <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono font-medium bg-navy-800 text-navy-300 border border-navy-700">
                                         {{ $role->permissions_count }} Permission
                                     </span>
                                 </td>
                                 {{-- Kolom Jumlah User --}}
                                 <td class="px-5 py-4 text-center">
                                     <span class="text-sm text-navy-400">
                                         {{ $role->users_count }} User
                                     </span>
                                 </td>
                                 {{-- Kolom Aksi --}}
                                 <td class="px-5 py-4 text-right">
                                     <div class="flex items-center justify-end gap-2">
                                         {{-- Detail --}}
                                         <a href="{{ route('settings.roles.show', $role) }}" 
                                            class="p-1.5 text-navy-400 hover:text-white hover:bg-navy-800 rounded-lg transition"
                                            title="Detail Role">
                                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                             </svg>
                                         </a>
                                         {{-- Edit --}}
                                         <a href="{{ route('settings.roles.edit', $role) }}" 
                                            class="p-1.5 text-navy-400 hover:text-gold-400 hover:bg-navy-800 rounded-lg transition"
                                            title="Edit Role">
                                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                             </svg>
                                         </a>
                                         {{-- Delete --}}
                                         @if(strtolower($role->name) !== 'owner')
                                             <form method="POST" action="{{ route('settings.roles.destroy', $role) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus role {{ $role->name }}? Aksi ini tidak dapat dibatalkan.');">
                                                 @csrf
                                                 @method('DELETE')
                                                 <button type="submit" class="p-1.5 text-navy-400 hover:text-red-400 hover:bg-navy-800 rounded-lg transition" title="Hapus Role">
                                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                     </svg>
                                                 </button>
                                             </form>
                                         @endif
                                     </div>
                                 </td>
                             </tr>
                         @endforeach
                     </tbody>
                 </table>
             </div>
             
             {{-- Pagination --}}
             @if($roles->hasPages())
                 <div class="px-5 py-4 border-t border-navy-800">
                     {{ $roles->links() }}
                 </div>
             @endif
         @else
             <div class="p-12">
                 <x-atelier.empty-state
                     title="Role Tidak Ditemukan"
                     message="Tidak ada data role yang sesuai dengan kriteria pencarian Anda."
                 />
             </div>
         @endif
     </x-atelier.card>
 </div>
</x-app-layout>