<?php

namespace App\Services;

use App\Models\Sales\SalesReturn;
use App\Models\Sales\SalesReturnItem;
use App\Models\Sales\DeliveryOrder;
use App\Models\Warehouse\Inventory;
use App\Models\Warehouse\StockMovement;
use Illuminate\Support\Facades\DB;

class SalesReturnService
{
    /**
     * Generate nomor retur unik per bulan.
     * Format: SR-YYYYMM-XXXX
     */
    public static function generateNumber(): string
    {
        $prefix = 'SR-' . date('Ym') . '-';

        $last = SalesReturn::where('return_number', 'like', "{$prefix}%")
            ->orderByDesc('return_number')
            ->first();

        $lastNumber = $last ? (int) substr($last->return_number, -4) : 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Buat data Retur baru (status draft).
     */
    public static function create(array $data): SalesReturn
    {
        DB::beginTransaction();
        try {
            $do = DeliveryOrder::findOrFail($data['delivery_order_id']);

            // 1. Buat header Retur
            $return = SalesReturn::create([
                'return_number'     => self::generateNumber(),
                'delivery_order_id' => $do->id,
                'customer_id'       => $do->customer_id,
                'return_date'       => $data['return_date'],
                'status'            => 'draft',
                'reason'            => $data['reason'],
                'total_amount'      => 0, // Akan diupdate setelah items
                'notes'             => $data['notes'] ?? null,
                'created_by'        => auth()->id(),
            ]);

            // 2. Buat items
            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $qty = (float) $item['qty'];
                $price = (float) $item['unit_price'];
                $subtotal = $qty * $price;
                $totalAmount += $subtotal;

                SalesReturnItem::create([
                    'sales_return_id'        => $return->id,
                    'delivery_order_item_id' => $item['delivery_order_item_id'],
                    'product_id'             => $item['product_id'],
                    'location_id'            => $item['location_id'],
                    'qty'                    => $qty,
                    'unit_price'             => $price,
                    'subtotal'               => $subtotal,
                    'notes'                  => $item['notes'] ?? null,
                ]);
            }

            // 3. Update total di header
            $return->update(['total_amount' => $totalAmount]);

            DB::commit();

            return $return->fresh(['items', 'customer', 'deliveryOrder']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Proses Retur (status draft -> completed).
     * Menambah stok kembali ke gudang.
     */
    public static function complete(SalesReturn $return): SalesReturn
    {
        if ($return->status !== 'draft') {
            throw new \Exception("Hanya retur berstatus draft yang bisa diproses.");
        }

        DB::beginTransaction();
        try {
            $return->load('items.product', 'items.location');

            foreach ($return->items as $item) {
                // 1. Tambah stok di Inventory
                $inventory = Inventory::where('item_type', 'product')
                    ->where('item_id', $item->product_id)
                    ->where('location_id', $item->location_id)
                    ->first();

                // Jika stok di lokasi tersebut belum ada, buat baru
                if (!$inventory) {
                    $inventory = Inventory::create([
                        'item_type'   => 'product',
                        'item_id'     => $item->product_id,
                        'location_id' => $item->location_id,
                        'qty'         => 0,
                    ]);
                }

                $qtyBefore = (float) $inventory->qty;
                $qtyAfter  = $qtyBefore + (float) $item->qty;

                // Update stok
                $inventory->update([
                    'qty'              => $qtyAfter,
                    'last_movement_at' => now(),
                ]);

                // 2. Catat di Stock Movement (IN)
                StockMovement::create([
                    'movement_number'  => StockMovement::generateNumber(),
                    'item_type'        => 'product',
                    'item_id'          => $item->product_id,
                    'location_id'      => $item->location_id,
                    'type'             => 'in',
                    'qty'              => $item->qty,
                    'qty_before'       => $qtyBefore,
                    'qty_after'        => $qtyAfter,
                    'reference_type'   => SalesReturn::class,
                    'reference_id'     => $return->id,
                    'reference_number' => $return->return_number,
                    'date'             => $return->return_date,
                    'notes'            => "Retur dari customer " . ($return->customer->nama ?? '-'),
                    'created_by'       => auth()->id(),
                ]);
            }

            // 3. Update status retur
            $return->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            return $return->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Batalkan Retur.
     */
    public static function cancel(SalesReturn $return): SalesReturn
    {
        if ($return->status === 'cancelled') {
            throw new \Exception('Retur ini sudah dibatalkan.');
        }

        DB::beginTransaction();
        try {
            // Jika retur yang sudah completed dibatalkan, stok harus dikurangi kembali
            if ($return->status === 'completed') {
                $return->load('items');

                foreach ($return->items as $item) {
                    $inventory = Inventory::where('item_type', 'product')
                        ->where('item_id', $item->product_id)
                        ->where('location_id', $item->location_id)
                        ->first();

                    if ($inventory) {
                        $qtyBefore = (float) $inventory->qty;
                        $qtyAfter  = $qtyBefore - (float) $item->qty;

                        $inventory->update([
                            'qty'              => $qtyAfter,
                            'last_movement_at' => now(),
                        ]);

                        // Catat stock movement (OUT) karena retur dibatalkan
                        StockMovement::create([
                            'movement_number'  => StockMovement::generateNumber(),
                            'item_type'        => 'product',
                            'item_id'          => $item->product_id,
                            'location_id'      => $item->location_id,
                            'type'             => 'out',
                            'qty'              => $item->qty,
                            'qty_before'       => $qtyBefore,
                            'qty_after'        => $qtyAfter,
                            'reference_type'   => SalesReturn::class,
                            'reference_id'     => $return->id,
                            'reference_number' => $return->return_number,
                            'date'             => now()->toDateString(),
                            'notes'            => "Pembatalan retur (stok ditarik kembali)",
                            'created_by'       => auth()->id(),
                        ]);
                    }
                }
            }

            $return->update(['status' => 'cancelled']);

            DB::commit();

            return $return->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}