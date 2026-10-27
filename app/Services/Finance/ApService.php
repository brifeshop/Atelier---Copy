<?php

namespace App\Services\Finance;

use App\Models\Finance\ApBill;
use App\Models\Purchasing\GoodsReceipt;
use Illuminate\Support\Facades\DB;

class ApService
{
    /**
     * Generate nomor AP unik per bulan.
     * Format: AP-YYYYMM-XXXX
     */
    public static function generateNumber(): string
    {
        $prefix = 'AP-' . date('Ym') . '-';

        $last = ApBill::where('ap_number', 'like', "{$prefix}%")
            ->orderByDesc('ap_number')
            ->first();

        $lastNumber = $last ? (int) substr($last->ap_number, -4) : 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Buat AP + jurnal dari Goods Receipt (Penerimaan Barang).
     */
        public static function createFromGoodsReceipt(GoodsReceipt $gr): ApBill
    {
        // Cegah duplikat
        $existing = ApBill::where('goods_receipt_id', $gr->id)->first();
        if ($existing) {
            return $existing;
        }

        // Hitung total dari item GR
        $gr->load('items');
        $total = 0;
        foreach ($gr->items as $item) {
            $total += ($item->qty_received * $item->unit_price);
        }

        if ($total <= 0) {
            throw new \Exception('Total nilai Goods Receipt tidak valid (0).');
        }

        DB::beginTransaction();
        try {
            // 1. Jurnal Penerimaan Barang
            $journal = JournalService::post([
                'journal_date'     => $gr->date,
                'reference_type'   => GoodsReceipt::class,
                'reference_id'     => $gr->id,
                'reference_number' => $gr->gr_number,
                'description'      => "Penerimaan barang {$gr->gr_number}",
            ], [
                [
                    'account_code' => '1-1310', // Persediaan Bahan Baku
                    'debit'        => $total,
                    'credit'       => 0,
                    'memo'         => "Penerimaan {$gr->gr_number}",
                    'supplier_id'  => $gr->supplier_id,
                ],
                [
                    'account_code' => '2-1000', // Hutang Usaha (AP)
                    'debit'        => 0,
                    'credit'       => $total,
                    'memo'         => "Hutang dari {$gr->gr_number}",
                    'supplier_id'  => $gr->supplier_id,
                ],
            ]);

            // 2. Buat Bill AP
            $ap = ApBill::create([
                'ap_number'        => self::generateNumber(),
                'goods_receipt_id' => $gr->id,
                'supplier_id'      => $gr->supplier_id,
                'bill_date'        => $gr->date,
                'due_date'         => now()->addDays(30)->toDateString(), // Default tempo 30 hari
                'total_amount'     => $total,
                'paid_amount'      => 0,
                'status'           => 'open',
                'journal_id'       => $journal->id,
                'created_by'       => auth()->id() ?? 2,
            ]);

            DB::commit();

            return $ap->fresh(['supplier', 'journal']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Terapkan pembayaran ke AP (partial/full).
     */
    public static function applyPayment(ApBill $ap, float $amount): ApBill
    {
        if ($ap->status === 'void') {
            throw new \Exception('AP ini sudah di-void.');
        }

        $outstanding = (float) ($ap->total_amount - $ap->paid_amount);

        if ($amount <= 0 || $amount > $outstanding) {
            throw new \Exception("Jumlah bayar tidak valid. Outstanding: {$outstanding}");
        }

        $newPaid   = (float) $ap->paid_amount + $amount;
        $newStatus = bccomp((string) $newPaid, (string) $ap->total_amount, 2) >= 0
            ? 'paid'
            : 'partial';

        $ap->update([
            'paid_amount' => $newPaid,
            'status'      => $newStatus,
        ]);

        return $ap->fresh();
    }
}