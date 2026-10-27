<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\ApBill;
use App\Models\Finance\ArInvoice;
use App\Models\Finance\Payment;

class FinanceDashboardController extends Controller
{
    public function index()
    {
        // Statistik AR
        $arOpen      = ArInvoice::whereIn('status', ['open', 'partial'])->count();
        $arTotal     = ArInvoice::whereIn('status', ['open', 'partial'])->sum('total_amount');
        $arOutstanding = ArInvoice::whereIn('status', ['open', 'partial'])
            ->selectRaw('SUM(total_amount - paid_amount) as outstanding')
            ->value('outstanding') ?? 0;

        // Statistik AP
        $apOpen      = ApBill::whereIn('status', ['open', 'partial'])->count();
        $apOutstanding = ApBill::whereIn('status', ['open', 'partial'])
            ->selectRaw('SUM(total_amount - paid_amount) as outstanding')
            ->value('outstanding') ?? 0;

        // Payment hari ini
        $payInToday  = Payment::where('direction', 'in')->whereDate('payment_date', today())->sum('total_amount');
        $payOutToday = Payment::where('direction', 'out')->whereDate('payment_date', today())->sum('total_amount');

        // Saldo kas & bank (dari COA 1-1000 dan 1-1100)
        $saldoKas  = 0;
        $saldoBank = 0;
        // Placeholder — nanti diisi setelah ada laporan GL
        // Untuk sekarang tampilkan 0 dulu

        // AR jatuh tempo (overdue)
        $arOverdue = ArInvoice::whereIn('status', ['open', 'partial'])
            ->where('due_date', '<', today())
            ->count();

        $recentAr = ArInvoice::with('customer')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentPayments = Payment::with(['customer', 'supplier'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('finance.dashboard', compact(
            'arOpen', 'arTotal', 'arOutstanding', 'arOverdue',
            'apOpen', 'apOutstanding',
            'payInToday', 'payOutToday',
            'saldoKas', 'saldoBank',
            'recentAr', 'recentPayments'
        ));
    }
}