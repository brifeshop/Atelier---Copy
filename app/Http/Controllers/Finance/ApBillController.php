<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\ApBill;
use Illuminate\Http\Request;

class ApBillController extends Controller
{
    public function index(Request $request)
    {
        $query = ApBill::with(['supplier', 'goodsReceipt']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ap_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn($s) => $s->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $apBills = $query->orderByDesc('bill_date')->paginate(20)->withQueryString();

        $stats = [
            'total'       => ApBill::count(),
            'open'        => ApBill::where('status', 'open')->count(),
            'partial'     => ApBill::where('status', 'partial')->count(),
            'outstanding' => ApBill::whereIn('status', ['open', 'partial'])
                ->selectRaw('SUM(total_amount - paid_amount) as o')
                ->value('o') ?? 0,
        ];

        return view('finance.ap.index', compact('apBills', 'stats'));
    }

    public function show(ApBill $ap)
    {
        $ap->load([
            'supplier',
            'goodsReceipt.items',
            'journal.items.account',
            'allocations.payment',
        ]);

        return view('finance.ap.show', compact('ap'));
    }
}