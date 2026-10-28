<?php

namespace App\Services;

use App\Models\Purchasing\QcInspection;
use App\Models\Purchasing\QcInspectionItem;
use App\Models\Warehouse\Location;
use App\Models\Warehouse\StockMovement;
use Illuminate\Support\Facades\DB;

class QcInspectionService
{
    public static function generateNumber(): string
    {
        $prefix = 'QC-' . date('Ym') . '-';
        $last = QcInspection::where('qc_number', 'like', "{$prefix}%")
            ->orderByDesc('qc_number')->first();
        $lastNumber = $last ? (int) substr($last->qc_number, -4) : 0;
        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Auto-create QC saat Goods Receipt di-receive.
     */
    public static function createFromGoodsReceipt($gr): QcInspection
    {
        $existing = QcInspection::where('goods_receipt_id', $gr->id)->first();
        if ($existing) return $existing;

        DB::beginTransaction();
        try {
            $qc = QcInspection::create([
                'qc_number' => self::generateNumber(),
                'goods_receipt_id' => $gr->id,
                'inspection_date' => now()->toDateString(),
                'status' => 'pending',
                'created_by' => auth()->id() ?? 2,
            ]);

            foreach ($gr->items as $item) {
                QcInspectionItem::create([
                    'qc_inspection_id' => $qc->id,
                    'goods_receipt_item_id' => $item->id,
                    'material_id' => $item->material_id,
                    'qty_received' => $item->qty_received,
                ]);
            }

            DB::commit();
            return $qc;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Complete QC & pindahkan stok.
     */
        public static function complete(QcInspection $qc, array $data)
    {
        if ($qc->status === 'completed') {
            throw new \Exception('QC ini sudah diselesaikan.');
        }

        DB::beginTransaction();
        try {
            $qc->load('items', 'goodsReceipt');
            $locKarantina = Location::where('kode', 'QC-KARANTINA')->firstOrFail();
            $locReject    = Location::where('kode', 'QC-REJECT')->firstOrFail();

            // Ambil lokasi tujuan asli dari GR (Gudang Utama yang dipilih user)
            $locUtama = $qc->goodsReceipt->items->first()->location_id ?? null;

            foreach ($qc->items as $index => $item) {
                $qtyOk = (float) ($data['items'][$index]['qty_ok'] ?? 0);
                $qtyReject = (float) ($data['items'][$index]['qty_reject'] ?? 0);

                // Update item QC
                $item->update([
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'reject_reason' => $data['items'][$index]['reject_reason'] ?? null,
                    'notes' => $data['items'][$index]['notes'] ?? null,
                ]);

                // Pindahkan stok qty OK: dari Karantina -> Gudang Utama
                if ($qtyOk > 0) {
                    StockMovementService::recordOut(
                        'material', $item->material_id, $locKarantina->id, $qtyOk,
                        ['reference_type' => QcInspection::class, 'reference_id' => $qc->id, 'reference_number' => $qc->qc_number, 'date' => now()->toDateString(), 'notes' => "Pindah dari Karantina ke Gudang Utama (QC OK)"]
                    );
                    StockMovementService::recordIn(
                        'material', $item->material_id, $locUtama, $qtyOk,
                        ['reference_type' => QcInspection::class, 'reference_id' => $qc->id, 'reference_number' => $qc->qc_number, 'date' => now()->toDateString(), 'notes' => "Penerimaan OK dari QC"]
                    );
                }

                // Pindahkan stok qty Reject: dari Karantina -> Gudang Reject
                if ($qtyReject > 0) {
                    StockMovementService::recordOut(
                        'material', $item->material_id, $locKarantina->id, $qtyReject,
                        ['reference_type' => QcInspection::class, 'reference_id' => $qc->id, 'reference_number' => $qc->qc_number, 'date' => now()->toDateString(), 'notes' => "Pindah dari Karantina ke Gudang Reject (QC Reject)"]
                    );
                    StockMovementService::recordIn(
                        'material', $item->material_id, $locReject->id, $qtyReject,
                        ['reference_type' => QcInspection::class, 'reference_id' => $qc->id, 'reference_number' => $qc->qc_number, 'date' => now()->toDateString(), 'notes' => "Barang Reject dari QC"]
                    );
                }
            }

            // Update status QC
            $qc->update([
                'status' => $qtyReject > 0 ? 'rejected' : 'completed', // Sesuaikan jika ada partial reject
                'inspection_method' => $data['inspection_method'],
                'sample_size' => $data['sample_size'],
                'inspector_id' => auth()->id() ?? 2,
                'notes' => $data['notes'] ?? null,
                'completed_at' => now(),
            ]);

            DB::commit();
            return $qc->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}