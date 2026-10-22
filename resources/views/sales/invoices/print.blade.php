<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { 
                size: A4; 
                margin: 10mm 12mm; 
            }
            body { 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
                background: white !important;
            }
            .no-print { display: none !important; }
            .print-border { border-color: #000 !important; }
        }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 py-6">

    <div class="max-w-4xl mx-auto bg-white p-8 shadow-sm print:shadow-none print:p-0 text-xs leading-relaxed">
        
        <!-- Header Dokumen (Standar ERP: Identitas Jelas & Minimalis Tinta) -->
        <div class="flex justify-between items-start border-b border-gray-400 pb-4 mb-4">
            <div class="flex items-center gap-3">
                <!-- Logo Perusahaan (Hitam putih bersih untuk hemat tinta) -->
                <div class="w-12 h-12 bg-gray-900 text-white flex items-center justify-center rounded font-serif font-bold text-xl">
                    A
                </div>
                <div>
                    <h1 class="text-lg font-bold uppercase tracking-tight text-gray-900">PT Atelier Indonesia</h1>
                    <p class="text-[11px] text-gray-600">Jl. Industri Raya No. 123, Jakarta Barat 11610</p>
                    <p class="text-[11px] text-gray-600">Telp: (021) 123-4567 | Email: finance@atelier.co.id</p>
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-black text-gray-800 tracking-wider">INVOICE</h2>
                <p class="text-sm font-bold font-mono mt-0.5 text-gray-900">{{ $invoice->invoice_number }}</p>
                
                <!-- Status Badge (Kontras tinggi, tanpa blok warna tebal agar hemat tinta) -->
                <div class="mt-1.5">
                    @if($invoice->isPaid())
                        <span class="inline-block px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase border border-gray-800 text-gray-900 bg-gray-100">
                            LUNAS (PAID)
                        </span>
                    @else
                        <span class="inline-block px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase border border-gray-800 text-gray-900 bg-white">
                            BELUM LUNAS (UNPAID)
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Informasi Transaksi & Relasi (Grid 2 Kolom Rapi ala ERP) -->
        <div class="grid grid-cols-2 gap-6 mb-6 text-xs bg-gray-50 p-3 border border-gray-300 rounded">
            <div>
                <p class="font-bold text-gray-700 uppercase tracking-wider mb-1 border-b border-gray-200 pb-0.5">Ditagihkan Kepada (Bill To):</p>
                <p class="text-sm font-bold text-gray-900 mt-1">{{ $invoice->customer->nama ?? '-' }}</p>
                <p class="text-gray-700 whitespace-pre-line mt-0.5">{{ $invoice->customer->address ?? '-' }}</p>
                <p class="text-gray-700 mt-0.5">Telp: {{ $invoice->customer->phone ?? '-' }}</p>
            </div>
            <div>
                <p class="font-bold text-gray-700 uppercase tracking-wider mb-1 border-b border-gray-200 pb-0.5">Informasi Dokumen:</p>
                <table class="w-full mt-1">
                    <tr>
                        <td class="text-gray-600 py-0.5 w-1/3">Tanggal Invoice:</td>
                        <td class="font-semibold text-gray-900">{{ format_tanggal($invoice->invoice_date) }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray-600 py-0.5">Jatuh Tempo:</td>
                        <td class="font-bold text-gray-900">{{ format_tanggal($invoice->due_date) }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray-600 py-0.5">No. Sales Order:</td>
                        <td class="font-mono font-medium">{{ $invoice->deliveryOrder->salesOrder->so_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray-600 py-0.5">No. Surat Jalan:</td>
                        <td class="font-mono font-medium">{{ $invoice->deliveryOrder->do_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray-600 py-0.5">Sales Person:</td>
                        <td class="font-medium">{{ $invoice->deliveryOrder->salesOrder->salesPerson->nama ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tabel Item Barang (Garis tipis, padat, sangat hemat tinta) -->
        <table class="w-full mb-6 border-collapse">
            <thead>
                <tr class="border-b-2 border-gray-800 text-gray-900">
                    <th class="text-left py-2 px-2 font-bold uppercase tracking-wider text-[11px]">Deskripsi Produk</th>
                    <th class="text-center py-2 px-2 font-bold uppercase tracking-wider text-[11px] w-16">Qty</th>
                    <th class="text-right py-2 px-2 font-bold uppercase tracking-wider text-[11px] w-28">Harga Satuan</th>
                    <th class="text-right py-2 px-2 font-bold uppercase tracking-wider text-[11px] w-28">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-300">
                @foreach($invoice->items as $item)
                    <tr>
                        <td class="py-2.5 px-2 text-gray-900">
                            <span class="font-medium">{{ $item->description }}</span>
                            @if($item->product && $item->product->kode)
                                <br><span class="font-mono text-[10px] text-gray-500">SKU: {{ $item->product->kode }}</span>
                            @endif
                        </td>
                        <td class="text-center py-2.5 px-2 font-mono text-gray-800">{{ number_format($item->qty, 2) }}</td>
                        <td class="text-right py-2.5 px-2 font-mono text-gray-800">{{ format_rupiah($item->unit_price) }}</td>
                        <td class="text-right py-2.5 px-2 font-mono font-bold text-gray-900">{{ format_rupiah($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Total, Rekening & Catatan (Layout Sejajar Standar Akuntansi) -->
        <div class="flex justify-between items-start border-t border-gray-400 pt-4 mb-8">
            <!-- Instruksi Pembayaran & Catatan -->
            <div class="w-1/2 pr-4">
                <div class="border border-gray-300 p-3 rounded mb-3 bg-gray-50">
                    <p class="font-bold text-gray-800 uppercase text-[10px] tracking-wider mb-1">Instruksi Pembayaran Transfer Bank:</p>
                    <p class="font-semibold text-gray-900">Bank BCA</p>
                    <p class="font-mono font-bold text-sm text-gray-900">123-456-7890</p>
                    <p class="text-gray-700 text-[11px]">a/n PT Atelier Indonesia</p>
                </div>
                
                @if($invoice->notes)
                    <div class="text-[11px]">
                        <p class="font-bold text-gray-700 mb-0.5">Catatan / Keterangan:</p>
                        <p class="text-gray-600 whitespace-pre-line italic">{{ $invoice->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Kalkulasi Nominal Total -->
            <div class="w-5/12">
                <table class="w-full text-xs">
                    <tr class="border-b border-gray-200">
                        <td class="py-1.5 text-gray-600">Subtotal</td>
                        <td class="py-1.5 text-right font-mono font-medium">{{ format_rupiah($invoice->subtotal) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr class="border-b border-gray-200">
                        <td class="py-1.5 text-gray-600">Diskon</td>
                        <td class="py-1.5 text-right font-mono font-medium text-red-700">- {{ format_rupiah($invoice->discount_amount) }}</td>
                    </tr>
                    @endif
                    <tr class="border-b border-gray-200">
                        <td class="py-1.5 text-gray-600">Pajak (PPN)</td>
                        <td class="py-1.5 text-right font-mono font-medium">{{ format_rupiah($invoice->tax_amount) }}</td>
                    </tr>
                    <tr class="border-b-2 border-gray-800 font-bold text-sm">
                        <td class="py-2 text-gray-900">TOTAL TAGIHAN</td>
                        <td class="py-2 text-right font-mono text-gray-900">{{ format_rupiah($invoice->total_amount) }}</td>
                    </tr>
                </table>
                
                @if($invoice->isPaid())
                    <div class="mt-2 text-right text-[10px] text-gray-600 font-medium">
                        Tercatat lunas pada: {{ format_tanggal($invoice->paid_at) }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Tanda Tangan / Otorisasi Dokumen Standar ERP -->
        <div class="mt-12 flex justify-between items-end text-xs pt-4">
            <div class="text-center w-1/3">
                <p class="text-gray-600 mb-12">Penerima / Customer,</p>
                <div class="border-t border-gray-400 mx-auto w-36 pt-1">
                    <p class="font-semibold text-gray-900 truncate">{{ $invoice->customer->nama ?? '( ......................... )' }}</p>
                </div>
            </div>
            <div class="text-center w-1/3">
                <p class="text-gray-600 mb-12">Hormat Kami,</p>
                <div class="border-t border-gray-400 mx-auto w-36 pt-1">
                    <p class="font-semibold text-gray-900">{{ $invoice->creator->name ?? 'Finance Dept' }}</p>
                    <p class="text-[10px] text-gray-500">PT Atelier Indonesia</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Tombol Print (Sembunyi saat dicetak) -->
    <div class="text-center mt-6 mb-8 no-print">
        <button onclick="window.print()" class="inline-flex items-center gap-2 bg-gray-900 text-white px-6 py-2.5 rounded shadow hover:bg-gray-800 transition font-semibold text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak / Simpan PDF
        </button>
    </div>

</body>
</html>