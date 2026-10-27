<?php

namespace App\Services\Finance;

use App\Models\Finance\ApBill;
use App\Models\Finance\ArInvoice;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\Payment;
use App\Models\Finance\PaymentAllocation;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Generate nomor payment unik per bulan.
     * Format: PAY-YYYYMM-XXXX
     */
    public static function generateNumber(): string
    {
        $prefix = 'PAY-' . date('Ym') . '-';

        $last = Payment::where('payment_number', 'like', "{$prefix}%")
            ->orderByDesc('payment_number')
            ->first();

        $lastNumber = $last ? (int) substr($last->payment_number, -4) : 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Terima pembayaran dari customer (payment in).
     *
     * $data = [
     *   'payment_date'      => '2026-10-27',
     *   'method'            => 'bank_transfer',
     *   'cash_account_code' => '1-1100',
     *   'customer_id'       => 4,
     *   'reference_number'  => 'TRX-123',
     *   'notes'             => '...',
     *   'allocations' => [
     *      ['ar_invoice_id' => 1, 'amount' => 1000000],
     *   ],
     * ]
     */
    public static function receiveFromCustomer(array $data): Payment
    {
        return self::create('in', $data);
    }

    /**
     * Bayar supplier (payment out).
     */
    public static function paySupplier(array $data): Payment
    {
        return self::create('out', $data);
    }

    /**
     * Inti pembuatan payment — dipakai oleh receiveFromCustomer() dan paySupplier().
     */
    protected static function create(string $direction, array $data): Payment
    {
        $allocations = $data['allocations'] ?? [];
        if (empty($allocations)) {
            throw new \Exception('Minimal satu alokasi pembayaran.');
        }

        $totalAmount = 0;
        foreach ($allocations as $a) {
            $totalAmount += (float) $a['amount'];
        }
        if ($totalAmount <= 0) {
            throw new \Exception('Total pembayaran harus lebih dari 0.');
        }

        DB::beginTransaction();
        try {
            $cashAccount = ChartOfAccount::byCode($data['cash_account_code']);

            if ($cashAccount->type !== 'asset') {
                throw new \Exception("Akun kas harus bertipe aset (contoh: 1-1000 atau 1-1100).");
            }

            // 1. Buat header Payment
            $payment = Payment::create([
                'payment_number'   => self::generateNumber(),
                'direction'        => $direction,
                'payment_date'     => $data['payment_date'],
                'method'           => $data['method'] ?? 'cash',
                'cash_account_id'  => $cashAccount->id,
                'customer_id'      => $direction === 'in' ? ($data['customer_id'] ?? null) : null,
                'supplier_id'      => $direction === 'out' ? ($data['supplier_id'] ?? null) : null,
                'total_amount'     => $totalAmount,
                'reference_number' => $data['reference_number'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'status'           => 'draft',
                'created_by'       => auth()->id(),
            ]);

            // 2. Alokasi ke AR/AP + update sub-ledger
            foreach ($allocations as $a) {
                if ($direction === 'in') {
                    $ar = ArInvoice::lockForUpdate()->findOrFail($a['ar_invoice_id']);

                    // Pastikan AR milik customer yang sama
                    if ($payment->customer_id && $ar->customer_id !== $payment->customer_id) {
                        throw new \Exception("AR {$ar->ar_number} bukan milik customer ini.");
                    }

                    ArService::applyPayment($ar, (float) $a['amount']);

                    PaymentAllocation::create([
                        'payment_id'       => $payment->id,
                        'allocatable_type' => 'ar',
                        'allocatable_id'   => $ar->id,
                        'amount'           => (float) $a['amount'],
                    ]);
                } else {
                    $ap = ApBill::lockForUpdate()->findOrFail($a['ap_bill_id']);

                    if ($payment->supplier_id && $ap->supplier_id !== $payment->supplier_id) {
                        throw new \Exception("AP {$ap->ap_number} bukan milik supplier ini.");
                    }

                    ApService::applyPayment($ap, (float) $a['amount']);

                    PaymentAllocation::create([
                        'payment_id'       => $payment->id,
                        'allocatable_type' => 'ap',
                        'allocatable_id'   => $ap->id,
                        'amount'           => (float) $a['amount'],
                    ]);
                }
            }

            // 3. Jurnal
            if ($direction === 'in') {
                $lines = [
                    [
                        'account_code' => $cashAccount->code,
                        'debit'        => $totalAmount,
                        'credit'       => 0,
                        'memo'         => "Terima pembayaran {$payment->payment_number}",
                        'customer_id'  => $payment->customer_id,
                    ],
                    [
                        'account_code' => '1-1200',
                        'debit'        => 0,
                        'credit'       => $totalAmount,
                        'memo'         => "Pelunasan piutang {$payment->payment_number}",
                        'customer_id'  => $payment->customer_id,
                    ],
                ];
            } else {
                $lines = [
                    [
                        'account_code' => '2-1000',
                        'debit'        => $totalAmount,
                        'credit'       => 0,
                        'memo'         => "Bayar hutang {$payment->payment_number}",
                        'supplier_id'  => $payment->supplier_id,
                    ],
                    [
                        'account_code' => $cashAccount->code,
                        'debit'        => 0,
                        'credit'       => $totalAmount,
                        'memo'         => "Pembayaran {$payment->payment_number}",
                        'supplier_id'  => $payment->supplier_id,
                    ],
                ];
            }

            $journal = JournalService::post([
                'journal_date'     => $payment->payment_date,
                'reference_type'   => Payment::class,
                'reference_id'     => $payment->id,
                'reference_number' => $payment->payment_number,
                'description'      => $direction === 'in'
                    ? "Penerimaan pembayaran {$payment->payment_number}"
                    : "Pembayaran supplier {$payment->payment_number}",
            ], $lines);

            // 4. Update status payment
            $payment->update([
                'status'     => 'posted',
                'journal_id' => $journal->id,
                'posted_at'  => now(),
            ]);

            DB::commit();

            return $payment->fresh(['allocations', 'journal', 'customer', 'supplier']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}