<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Purchasing\QcInspection;
use App\Services\QcInspectionService;
use Illuminate\Http\Request;

class QcInspectionController extends Controller
{
    public function index(Request $request)
    {
        $query = QcInspection::with(['goodsReceipt.supplier', 'inspector']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('qc_number', 'like', "%{$search}%")
                  ->orWhereHas('goodsReceipt', fn($gr) => $gr->where('gr_number', 'like', "%{$search}%"))
                  ->orWhereHas('goodsReceipt.supplier', fn($s) => $s->where('nama', 'like', "%{$search}%"));
            });
        }

        $inspections = $query->orderByDesc('inspection_date')->orderByDesc('id')->paginate(20)->withQueryString();

        $stats = [
            'pending'   => QcInspection::where('status', 'pending')->count(),
            'completed' => QcInspection::where('status', 'completed')->count(),
            'rejected'  => QcInspection::where('status', 'rejected')->count(),
        ];

        return view('purchasing.qc-inspections.index', compact('inspections', 'stats'));
    }

    public function show(QcInspection $qcInspection)
    {
        $qcInspection->load([
            'goodsReceipt.supplier',
            'items.material',
            'items.goodsReceiptItem.location',
            'inspector',
        ]);
        return view('purchasing.qc-inspections.show', ['qc' => $qcInspection]);
    }

    public function complete(Request $request, QcInspection $qcInspection)
    {
        $validated = $request->validate([
            'inspection_method' => ['required', 'string'],
            'sample_size'       => ['nullable', 'numeric', 'min:0'],
            'notes'             => ['nullable', 'string', 'max:1000'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.qty_ok'    => ['required', 'numeric', 'min:0'],
            'items.*.qty_reject'=> ['required', 'numeric', 'min:0'],
            'items.*.reject_reason' => ['nullable', 'string'],
            'items.*.notes'     => ['nullable', 'string', 'max:255'],
        ]);

        // Validasi: total qty_ok + qty_reject harus sama dengan qty_received
        foreach ($qcInspection->items as $index => $item) {
            $total = (float) $validated['items'][$index]['qty_ok'] + (float) $validated['items'][$index]['qty_reject'];
            if (bccomp((string) $total, (string) $item->qty_received, 2) !== 0) {
                return back()->withInput()->with('error', "Baris #" . ($index + 1) . " (" . $item->material->nama . "): Total Qty OK + Qty Reject (" . $total . ") tidak sama dengan Qty Datang (" . $item->qty_received . ").");
            }
        }

        try {
            QcInspectionService::complete($qcInspection, $validated);
            return redirect()
                ->route('purchasing.qc-inspections.show', $qcInspection)
                ->with('success', "QC {$qcInspection->qc_number} berhasil diselesaikan. Stok sudah dipindahkan.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyelesaikan QC: ' . $e->getMessage());
        }
    }
}