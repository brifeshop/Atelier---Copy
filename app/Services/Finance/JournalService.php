<?php

namespace App\Services\Finance;

use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalItem;
use Illuminate\Support\Facades\DB;

class JournalService
{
    /**
     * Generate nomor jurnal unik per bulan.
     * Format: JE-YYYYMM-XXXX
     */
    public static function generateNumber(): string
    {
        $prefix = 'JE-' . date('Ym') . '-';

        $last = Journal::where('journal_number', 'like', "{$prefix}%")
            ->orderByDesc('journal_number')
            ->first();

        $lastNumber = $last ? (int) substr($last->journal_number, -4) : 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Post jurnal double-entry.
     *
     * Contoh $lines:
     * [
     *   ['account_code' => '1-1200', 'debit' => 1000, 'credit' => 0, 'memo' => 'Invoice INV-...', 'customer_id' => 1],
     *   ['account_code' => '4-1000', 'debit' => 0, 'credit' => 1000, 'memo' => 'Penjualan'],
     * ]
     */
    public static function post(array $data, array $lines): Journal
    {
        // 1. Validasi balance
        $totalDebit  = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit  += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        if (bccomp((string) $totalDebit, (string) $totalCredit, 2) !== 0) {
            throw new \Exception("Jurnal tidak balance: debit {$totalDebit} vs credit {$totalCredit}");
        }

        if ($totalDebit <= 0) {
            throw new \Exception('Jurnal tidak boleh kosong.');
        }

        DB::beginTransaction();
        try {
            // 2. Buat header
            $journal = Journal::create([
                'journal_number'   => self::generateNumber(),
                'journal_date'     => $data['journal_date'] ?? now()->toDateString(),
                'reference_type'   => $data['reference_type'] ?? null,
                'reference_id'     => $data['reference_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'description'      => $data['description'],
                'status'           => 'posted',   // auto-journal langsung posted
                'total_debit'      => $totalDebit,
                'total_credit'     => $totalCredit,
                'created_by'       => auth()->id(),
                'posted_at'        => now(),
                'posted_by'        => auth()->id(),
            ]);

            // 3. Buat items
            foreach ($lines as $line) {
                $account = ChartOfAccount::byCode($line['account_code']);

                if ($account->is_header) {
                    throw new \Exception("Akun {$account->code} adalah header, tidak bisa diposting.");
                }

                $debit  = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                // Satu baris tidak boleh isi debit & credit sekaligus
                if ($debit > 0 && $credit > 0) {
                    throw new \Exception("Akun {$account->code}: satu baris tidak boleh debit & credit sekaligus.");
                }

                JournalItem::create([
                    'journal_id'  => $journal->id,
                    'account_id'  => $account->id,
                    'debit'       => $debit,
                    'credit'      => $credit,
                    'memo'        => $line['memo'] ?? null,
                    'customer_id' => $line['customer_id'] ?? null,
                    'supplier_id' => $line['supplier_id'] ?? null,
                ]);
            }

            DB::commit();

            return $journal->fresh(['items.account']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Void jurnal (reverse). Membuat jurnal balik agar history tetap utuh.
     */
    public static function void(Journal $journal, string $reason = ''): Journal
    {
        if ($journal->status !== 'posted') {
            throw new \Exception('Hanya jurnal posted yang bisa di-void.');
        }

        $journal->load('items.account');

        $reverseLines = $journal->items->map(fn($i) => [
            'account_code' => $i->account->code,
            'debit'        => (float) $i->credit,   // tukar debit <-> credit
            'credit'       => (float) $i->debit,
            'memo'         => "Reversal: {$reason}",
            'customer_id'  => $i->customer_id,
            'supplier_id'  => $i->supplier_id,
        ])->toArray();

        DB::beginTransaction();
        try {
            $reverse = self::post([
                'journal_date'     => now()->toDateString(),
                'reference_type'   => $journal->reference_type,
                'reference_id'     => $journal->reference_id,
                'reference_number' => $journal->reference_number,
                'description'      => "Void jurnal {$journal->journal_number}. {$reason}",
            ], $reverseLines);

            $journal->update(['status' => 'void']);

            DB::commit();

            return $reverse;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}