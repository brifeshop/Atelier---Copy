<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\ArInvoice;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\Payment;
use App\Models\Master\Customer;
use App\Services\Finance\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Daftar semua pembayaran (In & Out)
     */
    public function index(Request $request)
    {
        $query = Payment::with(['customer', 'supplier', 'cashAccount']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($c) => $c->where('nama', 'like', "%{$search}%"))
                  ->orWhereHas('supplier', fn($s) => $s->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        $payments = $query->orderByDesc('payment_date')->paginate(20)->withQueryString();

        $stats = [
            'in_today'  => Payment::where('direction', 'in')->whereDate('payment_date', today())->sum('total_amount'),
            'out_today' => Payment::where('direction', 'out')->whereDate('payment_date', today())->sum('total_amount'),
        ];

        return view('finance.payments.index', compact('payments', 'stats'));
    }

    /**
     * Form Terima Pembayaran (Payment In)
     */
    public function createIn(Request $request)
    {
        $customers = Customer::where('is_active', true)->orderBy('nama')->get();
        
        // Tangkap customer_id yang dipilih dari dropdown filter
        $selectedCustomerId = $request->input('customer_id');
        
        // Ambil AR hanya jika customer sudah dipilih
        $arInvoices = collect();
        if ($selectedCustomerId) {
            $arInvoices = ArInvoice::with('customer')
                ->where('customer_id', $selectedCustomerId)
                ->whereIn('status', ['open', 'partial'])
                ->orderBy('due_date')
                ->get();
        }

        $cashAccounts = ChartOfAccount::whereIn('code', ['1-1000', '1-1100'])->get();

        return view('finance.payments.create-in', compact('arInvoices', 'cashAccounts', 'customers', 'selectedCustomerId'));
    }

    /**
     * Simpan Pembayaran Customer
     */
    public function storeIn(Request $request)
    {
        // 1. Validasi form (customer_id TIDAK wajib, karena akan diambil dari AR)
        $validated = $request->validate([
            'payment_date'      => ['required', 'date'],
            'method'            => ['required', 'in:cash,bank_transfer,cheque,giro,other'],
            'cash_account_code' => ['required', 'string', 'exists:chart_of_accounts,code'],
            'reference_number'  => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'allocations'       => ['required', 'array', 'min:1'],
            'allocations.*.ar_invoice_id' => ['required', 'exists:ar_invoices,id'],
            'allocations.*.amount'        => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            // 2. Ambil data AR pertama untuk mendapatkan customer_id yang valid
            $firstAllocation = $validated['allocations'][0];
            $ar = ArInvoice::findOrFail($firstAllocation['ar_invoice_id']);
            
            // 3. Timpa customer_id dengan data dari AR (mencegah error "bukan milik customer ini")
            $validated['customer_id'] = $ar->customer_id;

            // 4. Panggil Service
            $payment = PaymentService::receiveFromCustomer($validated);

            return redirect()
                ->route('finance.payments.show', $payment)
                ->with('success', "Pembayaran {$payment->payment_number} berhasil dicatat.");
                
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

        public function createOut(Request $request)
    {
        $suppliers = \App\Models\Master\Supplier::where('is_active', true)->orderBy('nama')->get();
        $selectedSupplierId = $request->input('supplier_id');
        
        $apBills = collect();
        if ($selectedSupplierId) {
            $apBills = \App\Models\Finance\ApBill::with('supplier')
                ->where('supplier_id', $selectedSupplierId)
                ->whereIn('status', ['open', 'partial'])
                ->orderBy('due_date')
                ->get();
        }

        $cashAccounts = \App\Models\Finance\ChartOfAccount::whereIn('code', ['1-1000', '1-1100'])->get();

        return view('finance.payments.create-out', compact('apBills', 'cashAccounts', 'suppliers', 'selectedSupplierId'));
    }

    public function storeOut(Request $request)
    {
        $validated = $request->validate([
            'payment_date'      => ['required', 'date'],
            'method'            => ['required', 'in:cash,bank_transfer,cheque,giro,other'],
            'cash_account_code' => ['required', 'string', 'exists:chart_of_accounts,code'],
            'reference_number'  => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'allocations'       => ['required', 'array', 'min:1'],
            'allocations.*.ap_bill_id' => ['required', 'exists:ap_bills,id'],
            'allocations.*.amount'     => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            // Ambil supplier_id langsung dari AP yang dipilih
            $ap = \App\Models\Finance\ApBill::findOrFail($validated['allocations'][0]['ap_bill_id']);
            $validated['supplier_id'] = $ap->supplier_id;

            $payment = PaymentService::paySupplier($validated);

            return redirect()
                ->route('finance.payments.show', $payment)
                ->with('success', "Pembayaran hutang {$payment->payment_number} berhasil dicatat.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /**
     * Detail Pembayaran
     */
    public function show(Payment $payment)
    {
        $payment->load([
            'allocations.arInvoice.salesInvoice', 
            'customer', 
            'supplier',
            'cashAccount', 
            'journal.items.account'
        ]);
        
        return view('finance.payments.show', compact('payment'));
    }
}