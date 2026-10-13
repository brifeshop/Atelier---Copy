@props([
    'title' => 'Bulk Input',
    'subtitle' => 'Copy-paste dari Excel untuk input cepat',
    'action' => '',
    'columns' => [],      // [['key' => 'nama', 'label' => 'Nama', 'type' => 'text', 'required' => true], ...]
    'extraFields' => [],  // [['type' => 'select', 'name' => 'warehouse_id', 'label' => 'Warehouse', 'options' => [...], 'required' => true], ...]
    'backUrl' => '',
    'sample' => '',
])

@php
    $sampleLines = [];
    if ($sample) {
        $sampleLines = explode("\n", trim($sample));
    } else {
        // Generate sample from columns
        $sampleRow = [];
        foreach ($columns as $col) {
            if ($col['type'] ?? 'text' === 'number') {
                $sampleRow[] = '1000';
            } else {
                $sampleRow[] = 'Contoh ' . $col['label'];
            }
        }
        $sampleLines = [
            implode("\t", $sampleRow),
            implode("\t", array_fill(0, count($columns), 'Contoh 2')),
        ];
    }

    $sampleText = implode("\n", $sampleLines);
@endphp

<div class="p-6 lg:p-8 space-y-6 grid-bg" x-data="bulkInput({{ json_encode($columns) }})">

    {{-- PAGE HEADER --}}
    <x-atelier.page-header 
        segment="BULK" 
        :title="$title" 
        :subtitle="$subtitle" 
    />

    @if(session('success'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">
            {!! session('error') !!}
        </div>
    @endif

    @if(session('bulk_errors') && count(session('bulk_errors')) > 0)
        <div class="bg-orange-500/10 border border-orange-500/30 text-orange-400 px-4 py-3 rounded-lg text-sm">
            <p class="font-semibold mb-1">⚠️ Beberapa baris di-skip:</p>
            <ul class="list-disc list-inside space-y-0.5 max-h-40 overflow-y-auto">
                @foreach(session('bulk_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" x-ref="form">
        @csrf

        {{-- EXTRA FIELDS (misal: warehouse, category, dll) --}}
        @if(count($extraFields) > 0)
            <x-atelier.card title="Langkah 1: Informasi Umum" :brackets="true">
                <div class="grid grid-cols-1 md:grid-cols-{{ count($extraFields) <= 2 ? count($extraFields) : 3 }} gap-5">
                    @foreach($extraFields as $field)
                        @if($field['type'] === 'select')
                            <div>
                                <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">
                                    {{ $field['label'] }}
                                    @if($field['required'] ?? false) <span class="text-red-400">*</span> @endif
                                </label>
                                <select name="{{ $field['name'] }}" 
                                        @if($field['required'] ?? false) required @endif
                                        class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                                    <option value="">-- Pilih {{ $field['label'] }} --</option>
                                    @foreach($field['options'] as $optValue => $optLabel)
                                        <option value="{{ $optValue }}" @selected(old($field['name']) == $optValue)>
                                            {{ $optLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($field['type'] === 'text')
                            <div>
                                <label class="block text-xs font-semibold text-navy-300 uppercase tracking-wider mb-2">
                                    {{ $field['label'] }}
                                    @if($field['required'] ?? false) <span class="text-red-400">*</span> @endif
                                </label>
                                <input type="text" 
                                       name="{{ $field['name'] }}"
                                       value="{{ old($field['name'], $field['value'] ?? '') }}"
                                       @if($field['required'] ?? false) required @endif
                                       placeholder="{{ $field['placeholder'] ?? '' }}"
                                       class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition">
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-atelier.card>
        @endif

        {{-- STEP: PASTE --}}
        <x-atelier.card 
            :title="count($extraFields) > 0 ? 'Langkah 2: Paste dari Excel' : 'Langkah 1: Paste dari Excel'" 
            :brackets="true"
        >
            {{-- Info --}}
            <div class="mb-4 p-3 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                <p class="text-xs font-semibold text-blue-400 mb-2">📝 Cara Pakai:</p>
                <ol class="text-xs text-blue-300 space-y-1 list-decimal list-inside">
                    <li>Buka <strong>Excel / Google Sheets</strong></li>
                    <li>Isi kolom: 
                        @foreach($columns as $i => $col)
                            <strong>{{ $col['label'] }}</strong>@if(!$loop->last) | @endif
                        @endforeach
                    </li>
                    <li>Blok cell → <strong>Ctrl+C</strong></li>
                    <li>Paste di textarea → <strong>Ctrl+V</strong></li>
                    <li>Preview muncul otomatis → klik <strong>Simpan</strong></li>
                </ol>
            </div>

            {{-- Sample --}}
            <details class="mb-4">
                <summary class="cursor-pointer text-xs text-navy-400 hover:text-gold-500 transition">
                    📋 Lihat Contoh Format
                </summary>
                <div class="mt-2 p-3 bg-navy-900/50 border border-navy-800 rounded-lg">
                    <pre class="text-xs text-navy-300 font-mono whitespace-pre-wrap">{{ $sampleText }}</pre>
                    <p class="text-xs text-navy-500 mt-2">Format: <strong>Tab-separated</strong> (langsung dari Excel). Auto-detect juga mendukung koma, pipe, atau titik-koma.</p>
                </div>
            </details>

            {{-- Tombol Paste Clipboard --}}
            <div class="flex justify-end mb-2">
                <button type="button" 
                        @click="pasteFromClipboard()"
                        class="text-xs text-gold-500 hover:text-gold-400 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Paste dari Clipboard
                </button>
            </div>

            <textarea 
                x-model="pastedText"
                @input.debounce.300ms="parseText()"
                rows="10"
                placeholder="Paste dari Excel di sini..."
                class="w-full px-3 py-2.5 bg-navy-950 border border-navy-700 rounded-lg text-sm text-white font-mono placeholder-navy-500 focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 focus:outline-none transition"
            ></textarea>
        </x-atelier.card>

        {{-- PREVIEW --}}
        <div x-show="parsedItems.length > 0" x-cloak>
            <x-atelier.card 
                :title="count($extraFields) > 0 ? 'Langkah 3: Preview & Edit' : 'Langkah 2: Preview & Edit'" 
                :brackets="true" 
                padding="p-0"
            >
                <div class="px-5 py-3 bg-gold-500/10 border-b border-gold-500/30 flex items-center justify-between">
                    <p class="text-xs text-gold-400">
                        ✓ Terdeteksi <strong x-text="parsedItems.length"></strong> baris.
                        Edit kalau perlu, lalu klik Simpan.
                    </p>
                    <button type="button" @click="clearAll()" class="text-xs text-red-400 hover:text-red-300">
                        🗑️ Clear All
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-navy-950/50 border-b border-navy-800">
                            <tr>
                                <th class="px-3 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest w-12">#</th>
                                @foreach($columns as $col)
                                    <th class="px-3 py-3 text-left text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest">
                                        {{ $col['label'] }}
                                        @if($col['required'] ?? false)
                                            <span class="text-red-400">*</span>
                                        @endif
                                    </th>
                                @endforeach
                                <th class="px-3 py-3 text-right text-[10px] font-mono font-semibold text-navy-400 uppercase tracking-widest w-12">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-800">
                            <template x-for="(item, idx) in parsedItems" :key="idx">
                                <tr class="hover:bg-navy-800/30 transition">
                                    <td class="px-3 py-2 text-xs font-mono text-navy-500" x-text="idx + 1"></td>
                                    @foreach($columns as $col)
                                        <td class="px-3 py-2">
                                            @php $colType = $col['type'] ?? 'text'; @endphp
                                            @if($colType === 'number')
                                                <input type="number" 
                                                       :name="`items[${idx}][{{ $col['key'] }}]`"
                                                       x-model.number="item.{{ $col['key'] }}"
                                                       step="0.01"
                                                       min="0"
                                                       placeholder="0"
                                                       @if($col['required'] ?? false) required @endif
                                                       class="w-full px-2 py-1.5 bg-navy-950 border border-navy-700 rounded text-sm text-white text-right focus:border-gold-500 focus:outline-none">
                                            @elseif($colType === 'textarea')
                                                <textarea :name="`items[${idx}][{{ $col['key'] }}]`"
                                                          x-model="item.{{ $col['key'] }}"
                                                          rows="1"
                                                          placeholder="{{ $col['placeholder'] ?? '' }}"
                                                          class="w-full px-2 py-1.5 bg-navy-950 border border-navy-700 rounded text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:outline-none"></textarea>
                                            @else
                                                <input type="text" 
                                                       :name="`items[${idx}][{{ $col['key'] }}]`"
                                                       x-model="item.{{ $col['key'] }}"
                                                       placeholder="{{ $col['placeholder'] ?? '' }}"
                                                       @if($col['required'] ?? false) required @endif
                                                       class="w-full px-2 py-1.5 bg-navy-950 border border-navy-700 rounded text-sm text-white placeholder-navy-500 focus:border-gold-500 focus:outline-none">
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-2 text-right">
                                        <button type="button" @click="removeItem(idx)"
                                                class="text-xs text-red-400 hover:text-red-300">
                                            ✕
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </x-atelier.card>
        </div>

        {{-- EMPTY STATE --}}
        <x-atelier.card x-show="parsedItems.length === 0 && pastedText.length > 0" x-cloak :brackets="true">
            <div class="p-8 text-center">
                <p class="text-sm text-red-400">⚠️ Tidak ada data yang terdeteksi.</p>
                <p class="text-xs text-navy-500 mt-2">Cek format paste Anda — pastikan pakai separator yang benar (Tab dari Excel).</p>
            </div>
        </x-atelier.card>

        {{-- ACTIONS --}}
        <div class="flex items-center justify-between gap-3">
            <x-atelier.button :href="$backUrl ?: '#'" variant="ghost">Batal</x-atelier.button>

            <x-atelier.button type="submit" variant="primary" 
                              x-bind:disabled="parsedItems.length === 0"
                              ::class="parsedItems.length === 0 && 'opacity-50 cursor-not-allowed'">
                Simpan <span x-text="parsedItems.length"></span> Data
            </x-atelier.button>
        </div>

    </form>

</div>

@once
@push('scripts')
<script>
    function bulkInput(columns) {
        return {
            pastedText: '',
            parsedItems: [],

            async pasteFromClipboard() {
                try {
                    const text = await navigator.clipboard.readText();
                    this.pastedText = text;
                    this.parseText();
                } catch (err) {
                    alert('Gagal akses clipboard. Silakan paste manual dengan Ctrl+V.');
                }
            },

            parseText() {
                if (!this.pastedText || !this.pastedText.trim()) {
                    this.parsedItems = [];
                    return;
                }

                const lines = this.pastedText
                    .split('\n')
                    .map(l => l.replace(/\r/g, '').trim())
                    .filter(l => l !== '');

                if (lines.length === 0) {
                    this.parsedItems = [];
                    return;
                }

                // Auto-detect separator (prioritas: Tab > Pipe > Semicolon > Comma)
                const firstLine = lines[0];
                let separator = '\t';
                if (!firstLine.includes('\t')) {
                    if (firstLine.includes('|')) separator = '|';
                    else if (firstLine.includes(';')) separator = ';';
                    else if (firstLine.includes(',')) separator = ',';
                }

                this.parsedItems = lines.map(line => {
                    const parts = line.split(separator).map(p => p.trim());
                    const item = {};
                    columns.forEach((col, i) => {
                        let value = parts[i] ?? '';
                        // Convert empty to null
                        if (value === '') value = null;
                        // Number conversion
                        if ((col.type ?? 'text') === 'number' && value !== null) {
                            value = parseFloat(value);
                            if (isNaN(value)) value = null;
                        }
                        item[col.key] = value;
                    });
                    return item;
                }).filter(item => {
                    // Skip kalau semua required kosong
                    return columns.some(col => (col.required ?? false) && item[col.key]);
                });
            },

            removeItem(idx) {
                this.parsedItems.splice(idx, 1);
            },

            clearAll() {
                if (confirm('Hapus semua data di preview?')) {
                    this.pastedText = '';
                    this.parsedItems = [];
                }
            },
        };
    }
</script>
@endpush
@endonce