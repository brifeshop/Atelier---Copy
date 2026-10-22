<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesInvoice;
use App\Services\SalesInvoiceService;
use Illuminate\Http\Request;

class SalesInvoiceController extends Controller
{
    /**
     * List semua Invoice.
     */
    public function index(Request $request)
    {
        $query = SalesInvoice::with(['customer', 'deliveryOrder'])
            ->withCount('items');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($c) => $c->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('deliveryOrder', fn($d) => $d->where('do_number', 'like', "%{$search}%"));
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'total'     => SalesInvoice::count(),
            'unpaid'    => SalesInvoice::where('status', 'unpaid')->count(),
            'paid'      => SalesInvoice::where('status', 'paid')->count(),
            'total_omzet'=> SalesInvoice::where('status', 'paid')->sum('total_amount'),
        ];

        return view('sales.invoices.index', compact('invoices', 'stats'));
    }

    /**
     * Generate Invoice dari Delivery Order.
     * Dipanggil dari halaman detail DO (via POST).
     */
    public function store(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'invoice_date' => ['nullable', 'date'],
            'due_date'     => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $invoice = SalesInvoiceService::createFromDeliveryOrder($deliveryOrder, $request->all());

            return redirect()
                ->route('sales.invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_number} berhasil dibuat.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat Invoice: ' . $e->getMessage());
        }
    }

    /**
     * Detail Invoice.
     */
    public function show(SalesInvoice $invoice)
    {
        $invoice->load([
            'items.product',
            'customer',
            'deliveryOrder',
            'creator',
        ]);

        return view('sales.invoices.show', compact('invoice'));
    }

    /**
     * Halaman Print / PDF.
     */
    public function print(SalesInvoice $invoice)
    {
        $invoice->load(['items.product', 'customer', 'deliveryOrder']);
        return view('sales.invoices.print', compact('invoice'));
    }

    /**
     * Tandai Lunas.
     */
    public function markPaid(SalesInvoice $invoice)
    {
        try {
            SalesInvoiceService::markAsPaid($invoice);
            return back()->with('success', 'Invoice berhasil ditandai lunas.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal update Invoice: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan Invoice (Opsional).
     */
    public function cancel(SalesInvoice $invoice)
    {
        if ($invoice->isPaid()) {
            return back()->with('error', 'Invoice yang sudah lunas tidak bisa dibatalkan.');
        }

        $invoice->update(['status' => 'cancelled']);
        return back()->with('success', 'Invoice dibatalkan.');
    }
}