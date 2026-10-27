<?php

namespace Database\Seeders;

use App\Models\Finance\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Struktur COA standar Atelier.
     * Format baris: [code, name, type, normal_balance, parent_code, is_header]
     */
    public function run(): void
    {
        $accounts = [
            // ===== 1. ASET =====
            ['1-0000', 'ASET',                       'asset',     'debit',  null,     true],
            ['1-1000', 'Kas',                        'asset',     'debit',  '1-0000', false],
            ['1-1100', 'Bank',                       'asset',     'debit',  '1-0000', false],
            ['1-1200', 'Piutang Usaha (AR)',         'asset',     'debit',  '1-0000', false],
            ['1-1300', 'Persediaan Barang Jadi',     'asset',     'debit',  '1-0000', false],
            ['1-1310', 'Persediaan Bahan Baku',      'asset',     'debit',  '1-0000', false],
            ['1-1400', 'Persediaan Dalam Proses',    'asset',     'debit',  '1-0000', false],
            ['1-1500', 'Peralatan & Mesin',          'asset',     'debit',  '1-0000', false],
            ['1-1510', 'Akumulasi Penyusutan',       'asset',     'credit', '1-0000', false],

            // ===== 2. LIABILITAS =====
            ['2-0000', 'LIABILITAS',                 'liability', 'credit', null,     true],
            ['2-1000', 'Hutang Usaha (AP)',          'liability', 'credit', '2-0000', false],
            ['2-1100', 'Hutang Pajak (PPN)',         'liability', 'credit', '2-0000', false],
            ['2-1200', 'Hutang Gaji',                'liability', 'credit', '2-0000', false],

            // ===== 3. EKUITAS =====
            ['3-0000', 'EKUITAS',                    'equity',    'credit', null,     true],
            ['3-1000', 'Modal Pemilik',              'equity',    'credit', '3-0000', false],
            ['3-2000', 'Laba Ditahan',               'equity',    'credit', '3-0000', false],
            ['3-3000', 'Laba/Rugi Tahun Berjalan',   'equity',    'credit', '3-0000', false],

            // ===== 4. PENDAPATAN =====
            ['4-0000', 'PENDAPATAN',                 'revenue',   'credit', null,     true],
            ['4-1000', 'Penjualan',                  'revenue',   'credit', '4-0000', false],
            ['4-2000', 'Retur Penjualan',            'revenue',   'debit',  '4-0000', false],
            ['4-3000', 'Diskon Penjualan',           'revenue',   'debit',  '4-0000', false],
            ['4-4000', 'Pendapatan Lain-lain',       'revenue',   'credit', '4-0000', false],

            // ===== 5. HPP =====
            ['5-0000', 'HARGA POKOK PENJUALAN',      'expense',   'debit',  null,     true],
            ['5-1000', 'HPP Barang Jadi',            'expense',   'debit',  '5-0000', false],
            ['5-2000', 'HPP Bahan Baku',             'expense',   'debit',  '5-0000', false],

            // ===== 6. BEBAN OPERASIONAL =====
            ['6-0000', 'BEBAN OPERASIONAL',          'expense',   'debit',  null,     true],
            ['6-1000', 'Beban Gaji',                 'expense',   'debit',  '6-0000', false],
            ['6-1100', 'Beban Listrik & Air',        'expense',   'debit',  '6-0000', false],
            ['6-1200', 'Beban Sewa',                 'expense',   'debit',  '6-0000', false],
            ['6-1300', 'Beban Penyusutan',           'expense',   'debit',  '6-0000', false],
            ['6-1400', 'Beban Overhead Produksi',    'expense',   'debit',  '6-0000', false],
            ['6-9000', 'Beban Lain-lain',            'expense',   'debit',  '6-0000', false],
        ];

        // Map untuk cari parent by code
        $codeToId = [];

        foreach ($accounts as [$code, $name, $type, $normal, $parentCode, $isHeader]) {
            $parentId = $parentCode ? ($codeToId[$parentCode] ?? null) : null;
            $level    = $parentCode ? (ChartOfAccount::find($parentId)?->level ?? 1) + 1 : 1;

            $account = ChartOfAccount::updateOrCreate(
                ['code' => $code],
                [
                    'name'           => $name,
                    'type'           => $type,
                    'normal_balance' => $normal,
                    'parent_id'      => $parentId,
                    'level'          => $level,
                    'is_header'      => $isHeader,
                    'is_active'      => true,
                ]
            );

            $codeToId[$code] = $account->id;
        }
    }
}