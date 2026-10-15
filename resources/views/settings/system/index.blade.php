<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl font-bold text-white">Audit Logs</h2>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6 grid-bg">
        <x-atelier.page-header
            segment="SETTINGS"
            title="Jejak Audit Aktivitas"
            subtitle="Pantau riwayat perubahan data dan akses sensitif oleh pengguna"
        />

        {{-- Card Filter --}}
        <x-atelier.card :brackets="true" padding="p-0">
            <div class="px-5 py-4 border-b border-navy-800">
                <form method="GET" action="{{ route('settings.audit.logs') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Filter Pengguna</label>
                        <select name="user_id" class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                            <option value="">-- Semua Pengguna --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="w-40">
                        <label class="block text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" 
                            class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                    </div>

                    <div class="w-40">
                        <label class="block text-[10px] font-mono text-navy-500 uppercase tracking-widest mb-1">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" 
                            class="w-full px-3 py-2 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                    </div>

                    <x-atelier.button type="submit" variant="secondary">Terapkan</x-atelier.button>
                    @if(request()->hasAny(['user_id', 'date_from', 'date_to']))
                        <x-atelier.button :href="route('settings.audit.logs')" variant="ghost">Reset</x-atelier.button>
                    @endif
                </form>
            </div>

            @if($logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-navy-950/60 border-b border-navy-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Waktu</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Pengguna</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Aktivitas</th>
                                <th class="px-5 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">Detail Properti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800/80">
                            @foreach($logs as $log)
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-5 py-4 text-xs font-mono text-navy-400 whitespace-nowrap">
                                        {{ $log->created_at->format('d M Y H:i:s') }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-navy-800 border border-navy-700 flex items-center justify-center text-[10px] font-bold text-gold-400">
                                                {{ strtoupper(substr($log->causer?->name ?? 'SYS', 0, 1)) }}
                                            </div>
                                            <span class="text-sm text-white">{{ $log->causer?->name ?? 'System' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-navy-800 text-navy-300 border border-navy-700">
                                            {{ $log->description }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-navy-500 font-mono max-w-md truncate" title="{{ json_encode($log->properties) }}">
                                        {{ json_encode($log->properties) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($logs->hasPages())
                    <div class="px-5 py-4 border-t border-navy-800">
                        {{ $logs->links() }}
                    </div>
                @endif
            @else
                <div class="p-12">
                    <x-atelier.empty-state
                        title="Tidak Ada Jejak Audit"
                        message="Belum ada aktivitas tercatat dalam rentang waktu atau filter yang dipilih."
                    />
                </div>
            @endif
        </x-atelier.card>
    </div>
</x-app-layout>