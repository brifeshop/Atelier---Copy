<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function trialBalance(Request $request)
    {
        // Default periode: awal bulan ini sampai hari ini
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date', now()->toDateString());

        // Ambil semua akun (kecuali header)
        $accounts = ChartOfAccount::where('is_header', false)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        // Hitung total debit/credit per akun dari journal_items
        // Hanya jurnal status 'posted' dalam rentang tanggal
        $totals = DB::table('journal_items')
            ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
            ->where('journals.status', 'posted')
            ->whereBetween('journals.journal_date', [$startDate, $endDate])
            ->select(
                'journal_items.account_id',
                DB::raw('SUM(journal_items.debit) as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->groupBy('journal_items.account_id')
            ->get()
            ->keyBy('account_id');

        // Gabungkan data akun dengan total
        $rows = $accounts->map(function ($account) use ($totals) {
            $total = $totals->get($account->id);
            $totalDebit  = $total ? (float) $total->total_debit : 0;
            $totalCredit = $total ? (float) $total->total_credit : 0;

            // Hitung saldo akhir sesuai tipe normal akun
            if ($account->normal_balance === 'debit') {
                $saldo = $totalDebit - $totalCredit;
            } else {
                $saldo = $totalCredit - $totalDebit;
            }

            return [
                'id'             => $account->id,
                'code'           => $account->code,
                'name'           => $account->name,
                'type'           => $account->type,
                'normal_balance' => $account->normal_balance,
                'total_debit'    => $totalDebit,
                'total_credit'   => $totalCredit,
                'saldo'          => $saldo,
            ];
        });

        // Hitung total keseluruhan
        $grandTotalDebit  = $rows->sum('total_debit');
        $grandTotalCredit = $rows->sum('total_credit');

        // Kelompokkan per tipe untuk summary
        $summary = [
            'asset'     => $rows->where('type', 'asset')->sum('saldo'),
            'liability' => $rows->where('type', 'liability')->sum('saldo'),
            'equity'    => $rows->where('type', 'equity')->sum('saldo'),
            'revenue'   => $rows->where('type', 'revenue')->sum('saldo'),
            'expense'   => $rows->where('type', 'expense')->sum('saldo'),
        ];

        return view('finance.reports.trial-balance', compact(
            'rows',
            'startDate',
            'endDate',
            'grandTotalDebit',
            'grandTotalCredit',
            'summary'
        ));
    }
        public function generalLedger(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date', now()->toDateString());
        $accountId = $request->input('account_id');

        $accounts = ChartOfAccount::where('is_header', false)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $selectedAccount = null;
        $mutations = collect();
        $saldoAwal = 0;
        $saldoAkhir = 0;

        if ($accountId) {
            $selectedAccount = ChartOfAccount::findOrFail($accountId);

            // Hitung Saldo Awal (sebelum start_date)
            $awalQuery = DB::table('journal_items')
                ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
                ->where('journals.status', 'posted')
                ->where('journal_items.account_id', $accountId)
                ->where('journals.journal_date', '<', $startDate . ' 00:00:00')
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $awalDebit  = $awalQuery ? (float) $awalQuery->total_debit : 0;
            $awalCredit = $awalQuery ? (float) $awalQuery->total_credit : 0;

            if ($selectedAccount->normal_balance === 'debit') {
                $saldoAwal = $awalDebit - $awalCredit;
            } else {
                $saldoAwal = $awalCredit - $awalDebit;
            }

            // Ambil Mutasi (dalam rentang tanggal)
            $mutations = DB::table('journal_items')
                ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
                ->where('journals.status', 'posted')
                ->where('journal_items.account_id', $accountId)
                ->whereBetween('journals.journal_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->select(
                    'journals.journal_date',
                    'journals.journal_number',
                    'journals.description',
                    'journal_items.debit',
                    'journal_items.credit',
                    'journal_items.memo'
                )
                ->orderBy('journals.journal_date', 'asc')
                ->orderBy('journals.id', 'asc')
                ->get();

            // Hitung Saldo Akhir (running balance akan dihitung di view)
            $totalDebit  = $mutations->sum('debit');
            $totalCredit = $mutations->sum('credit');

            if ($selectedAccount->normal_balance === 'debit') {
                $saldoAkhir = $saldoAwal + ($totalDebit - $totalCredit);
            } else {
                $saldoAkhir = $saldoAwal + ($totalCredit - $totalDebit);
            }
        }

        return view('finance.reports.general-ledger', compact(
            'accounts',
            'selectedAccount',
            'accountId',
            'mutations',
            'startDate',
            'endDate',
            'saldoAwal',
            'saldoAkhir'
        ));
    }
}