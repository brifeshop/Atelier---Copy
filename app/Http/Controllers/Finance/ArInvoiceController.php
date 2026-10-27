<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\ArInvoice;
use App\Models\Finance\Payment;
use App\Models\Master\Customer;
use Illuminate\Http\Request;

class ArInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = ArInvoice::with(['customer', 'salesInvoice'])
            ->withCount('allocations');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ar_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($c) => $c->where('nama', 'like', "%{$search}%"))
                  ->orWhereHas('salesInvoice', fn($s) => $s->where('invoice_number', 'like', "%{$search}%"));
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter overdue
        if ($request->filled('overdue') && $request->overdue == '1') {
            $query->whereIn('status', ['open', 'partial'])
              ->where('due_date', '<', today());
        }

        $arInvoices = $query->orderByDesc('invoice_date')->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total'          => ArInvoice::count(),
            'open'           => ArInvoice::where('status', 'open')->count(),
            'partial'        => ArInvoice::where('status', 'partial')->count(),
            'paid'           => ArInvoice::where('status', 'paid')->count(),
            'outstanding'    => ArInvoice::whereIn('status', ['open', 'partial'])
                ->selectRaw('SUM(total_amount - paid_amount) as o')
                ->value('o') ?? 0,
        ];

        $customers = Customer::where('is_active', true)->orderBy('nama')->get(['id', 'nama']);

        return view('finance.ar.index', compact('arInvoices', 'stats', 'customers'));
    }

        public function show(ArInvoice $ar)
    {
        // Paksa load semua relasi yang dibutuhkan view
        $ar->load([
            'customer',
            'salesInvoice.items.product',
            'journal.items.account',   // <--- Ini yang bikin tabel jurnal muncul
            'allocations.payment',
        ]);

        return view('finance.ar.show', compact('ar'));
    }
}