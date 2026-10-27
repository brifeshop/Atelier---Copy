<?php

namespace App\Services\Finance;

use App\Models\Finance\ArInvoice;
use App\Models\Sales\SalesInvoice;
use Illuminate\Support\Facades\DB;

class ArService
{
    /**
     * Generate nomor AR unik per bulan.
     * Format: AR-YYYYMM-XXXX
     */
    public static function generateNumber(): string
    {
        $prefix = 'AR-' . date('Ym') . '-';

        $last = ArInvoice::where('ar_number', 'like', "{$prefix}%")
            ->orderByDesc('ar_number')
            ->first();

        $lastNumber = $last ? (int) substr($last->ar_number, -4) : 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Buat AR + jurnal dari Sales Invoice.
     * Idempotent: jika AR untuk invoice ini sudah ada, kembalikan yang existing.
     */
    public static function createFromSalesInvoice(SalesInvoice $invoice): ArInvoice
    {
        // Idempotent guard
        $existing = ArInvoice::where('sales_invoice_id', $invoice->id)->first();
        if ($existing) {
            return $existing;
        }

        if ((float) $invoice->total_amount <= 0) {
            throw new \Exception('Invoice total harus lebih dari 0.');
        }

        DB::beginTransaction();
        try {
            // 1. Jurnal penjualan
            $lines = [
                [
                    'account_code' => '1-1200',
                    'debit'        => (float) $invoice->total_amount,
                    'credit'       => 0,
                    'memo'         => "Invoice {$invoice->invoice_number}",
                    'customer_id'  => $invoice->customer_id,
                ],
                [
                    'account_code' => '4-1000',
                    'debit'        => 0,
                    'credit'       => (float) ($invoice->subtotal - $invoice->discount_amount),
                    'memo'         => "Penjualan {$invoice->invoice_number}",
                    'customer_id'  => $invoice->customer_id,
                ],
            ];

            if ((float) $invoice->tax_amount > 0) {
                $lines[] = [
                    'account_code' => '2-1100',
                    'debit'        => 0,
                    'credit'       => (float) $invoice->tax_amount,
                    'memo'         => "PPN Invoice {$invoice->invoice_number}",
                    'customer_id'  => $invoice->customer_id,
                ];
            }

            $journal = JournalService::post([
                'journal_date'     => $invoice->invoice_date,
                'reference_type'   => SalesInvoice::class,
                'reference_id'     => $invoice->id,
                'reference_number' => $invoice->invoice_number,
                'description'      => "Penjualan Invoice {$invoice->invoice_number}",
            ], $lines);

            // 2. Buat AR
            $ar = ArInvoice::create([
                'ar_number'        => self::generateNumber(),
                'sales_invoice_id' => $invoice->id,
                'customer_id'      => $invoice->customer_id,
                'invoice_date'     => $invoice->invoice_date,
                'due_date'         => $invoice->due_date,
                'total_amount'     => (float) $invoice->total_amount,
                'paid_amount'      => 0,
                'status'           => 'open',
                'journal_id'       => $journal->id,
                'created_by'       => auth()->id(),
            ]);

            DB::commit();

            return $ar->fresh(['customer', 'journal']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Terapkan pembayaran ke AR (partial/full).
     */
    public static function applyPayment(ArInvoice $ar, float $amount): ArInvoice
    {
        if ($ar->status === 'void') {
            throw new \Exception('AR ini sudah di-void.');
        }

        $outstanding = $ar->outstanding;

        if ($amount <= 0 || $amount > $outstanding) {
            throw new \Exception("Jumlah bayar tidak valid. Outstanding: {$outstanding}");
        }

        $newPaid   = (float) $ar->paid_amount + $amount;
        $newStatus = bccomp((string) $newPaid, (string) $ar->total_amount, 2) >= 0
            ? 'paid'
            : 'partial';

        $ar->update([
            'paid_amount' => $newPaid,
            'status'      => $newStatus,
        ]);

        return $ar->fresh();
    }
}