<?php

namespace App\Services;

use App\Models\Master\Product;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\DeliveryOrderItem;
use App\Models\Sales\DeliveryOrderStatusLog;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderItem;
use App\Models\Warehouse\Inventory;
use App\Models\Warehouse\StockMovement;
use Illuminate\Support\Facades\DB;

class DeliveryOrderService
{
    /**
     * Generate nomor DO unik per hari.
     * Format: DO-YYYYMMDD-XXXX
     */
    public static function generateNumber(): string
    {
        $prefix = 'DO-' . date('Ymd') . '-';

        $last = DeliveryOrder::where('do_number', 'like', "{$prefix}%")
            ->orderByDesc('do_number')
            ->first();

        $lastNumber = $last ? (int) substr($last->do_number, -4) : 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Buat Delivery Order baru dari Sales Order.
     */
    public static function create(array $data): DeliveryOrder
    {
        DB::beginTransaction();
        try {
            $so = SalesOrder::findOrFail($data['sales_order_id']);

            // 1. Buat header DO
            $do = DeliveryOrder::create([
                'do_number'        => self::generateNumber(),
                'sales_order_id'   => $so->id,
                'customer_id'      => $so->customer_id,
                'warehouse_id'     => $data['warehouse_id'],
                'status'           => 'draft',
                'delivery_date'    => $data['delivery_date'],
                'shipping_address' => $data['shipping_address'] ?? $so->customer->address,
                'vehicle_number'   => $data['vehicle_number'] ?? null,
                'driver_name'      => $data['driver_name'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'created_by'       => auth()->id(),
            ]);

            // 2. Buat items
            foreach ($data['items'] as $item) {
                DeliveryOrderItem::create([
                    'delivery_order_id'   => $do->id,
                    'sales_order_item_id' => $item['sales_order_item_id'],
                    'product_id'          => $item['product_id'],
                    'location_id'         => $item['location_id'],
                    'qty_ordered'         => $item['qty_ordered'],
                    'qty_delivered'       => $item['qty_delivered'],
                    'qty_shipped'         => 0,
                    'notes'               => $item['notes'] ?? null,
                ]);
            }

            // 3. Log status awal
            self::log($do, null, 'draft', 'Delivery Order dibuat');

            DB::commit();

            return $do->fresh(['items.product', 'customer', 'salesOrder', 'warehouse']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update DO — hanya saat draft/ready.
     */
    public static function update(DeliveryOrder $do, array $data): DeliveryOrder
    {
        if (!$do->isEditable()) {
            throw new \Exception("DO dengan status {$do->status} tidak dapat diedit.");
        }

        DB::beginTransaction();
        try {
            // 1. Update header
            $do->update([
                'warehouse_id'     => $data['warehouse_id'],
                'delivery_date'    => $data['delivery_date'],
                'shipping_address' => $data['shipping_address'] ?? $do->shipping_address,
                'vehicle_number'   => $data['vehicle_number'] ?? null,
                'driver_name'      => $data['driver_name'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);

            // 2. Hapus item lama, buat ulang
            $do->items()->delete();

            foreach ($data['items'] as $item) {
                DeliveryOrderItem::create([
                    'delivery_order_id'   => $do->id,
                    'sales_order_item_id' => $item['sales_order_item_id'],
                    'product_id'          => $item['product_id'],
                    'location_id'         => $item['location_id'],
                    'qty_ordered'         => $item['qty_ordered'],
                    'qty_delivered'       => $item['qty_delivered'],
                    'qty_shipped'         => 0,
                    'notes'               => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return $do->fresh(['items.product', 'customer', 'salesOrder', 'warehouse']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark ready — draft → ready.
     */
    public static function markReady(DeliveryOrder $do): DeliveryOrder
    {
        if ($do->status !== 'draft') {
            throw new \Exception("Hanya DO berstatus draft yang bisa di-mark ready. Status: {$do->status}");
        }

        DB::beginTransaction();
        try {
            self::log($do, 'draft', 'ready', 'DO siap dikirim');
            $do->update(['status' => 'ready']);
            DB::commit();

            return $do->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Ship DO — kurangi stok produk jadi.
     */
    public static function ship(DeliveryOrder $do): DeliveryOrder
    {
        if ($do->status !== 'ready') {
            throw new \Exception("Hanya DO berstatus ready yang bisa dikirim. Status: {$do->status}");
        }

        $do->load(['items.product', 'items.location']);

        // 1. Cek stok dulu
        foreach ($do->items as $item) {
            $inventory = Inventory::where('item_type', 'product')
                ->where('item_id', $item->product_id)
                ->where('location_id', $item->location_id)
                ->first();

            $available = $inventory ? (float) $inventory->qty : 0;
            $requested = (float) $item->qty_delivered;

            if ($available < $requested) {
                $productName = $item->product->nama ?? "ID {$item->product_id}";
                throw new \Exception(
                    "Stok tidak cukup untuk {$productName}. " .
                    "Diminta: {$requested}, tersedia: {$available}."
                );
            }
        }

        DB::beginTransaction();
        try {
            // 2. Update stok + catat movement OUT
            foreach ($do->items as $item) {
                $inventory = Inventory::where('item_type', 'product')
                    ->where('item_id', $item->product_id)
                    ->where('location_id', $item->location_id)
                    ->first();

                $qtyBefore = (float) $inventory->qty;
                $qtyAfter  = $qtyBefore - (float) $item->qty_delivered;

                // Update inventory
                $inventory->update([
                    'qty'              => $qtyAfter,
                    'last_movement_at' => now(),
                ]);

                // Catat stock movement (OUT)
                StockMovement::create([
                    'movement_number'  => StockMovement::generateNumber(),
                    'item_type'        => 'product',
                    'item_id'          => $item->product_id,
                    'location_id'      => $item->location_id,
                    'type'             => 'out',
                    'qty'              => $item->qty_delivered,
                    'qty_before'       => $qtyBefore,
                    'qty_after'        => $qtyAfter,
                    'reference_type'   => DeliveryOrder::class,
                    'reference_id'     => $do->id,
                    'reference_number' => $do->do_number,
                    'date'             => $do->delivery_date,
                    'notes'            => "Delivery Order {$do->do_number} ke {$do->customer->nama}",
                    'created_by'       => auth()->id(),
                ]);

                // Update qty_shipped di item
                $item->update(['qty_shipped' => $item->qty_delivered]);
            }

            // 3. Update status DO
            self::log($do, 'ready', 'shipped', 'DO dikirim — stok produk dikurangi');
            $do->update([
                'status'     => 'shipped',
                'shipped_at' => now(),
                'shipped_by' => auth()->id(),
            ]);

            DB::commit();

            return $do->fresh(['items.product', 'customer', 'salesOrder', 'warehouse']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Deliver DO — customer terima barang.
     */
    public static function deliver(DeliveryOrder $do): DeliveryOrder
    {
        if ($do->status !== 'shipped') {
            throw new \Exception("Hanya DO berstatus shipped yang bisa di-deliver. Status: {$do->status}");
        }

        DB::beginTransaction();
        try {
            self::log($do, 'shipped', 'delivered', 'DO diterima customer');
            $do->update([
                'status'       => 'delivered',
                'delivered_at' => now(),
                'delivered_by' => auth()->id(),
            ]);

            // Update status SO otomatis kalau semua terkirim
            self::checkAndUpdateSalesOrder($do);

            DB::commit();

            return $do->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel DO.
     */
    public static function cancel(DeliveryOrder $do, ?string $reason = null): DeliveryOrder
    {
        if (!$do->canBeCancelled()) {
            throw new \Exception("DO dengan status {$do->status} tidak dapat dibatalkan.");
        }

        DB::beginTransaction();
        try {
            self::log($do, $do->status, 'cancelled', $reason ?? 'DO dibatalkan');
            $do->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
            ]);
            DB::commit();

            return $do->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Hapus DO — hanya draft.
     */
    public static function delete(DeliveryOrder $do): void
    {
        if ($do->status !== 'draft') {
            throw new \Exception('Hanya DO berstatus draft yang bisa dihapus.');
        }

        $do->delete();
    }

    /**
     * Cek apakah semua item SO sudah terkirim.
     * Kalau ya, update status SO via SalesOrderStatusService.
     */
    protected static function checkAndUpdateSalesOrder(DeliveryOrder $do): void
    {
        $so = $do->salesOrder;

        if (!$so || in_array($so->status, ['delivered', 'paid', 'closed', 'cancelled'])) {
            return;
        }

        $allDelivered = true;

        foreach ($so->items as $soItem) {
            $totalShipped = DeliveryOrderItem::where('sales_order_item_id', $soItem->id)
                ->whereHas('deliveryOrder', function ($q) {
                    $q->whereIn('status', ['shipped', 'delivered']);
                })
                ->sum('qty_shipped');

            if ((float) $totalShipped < (float) $soItem->qty) {
                $allDelivered = false;
                break;
            }
        }

        if ($allDelivered) {
            SalesOrderStatusService::onDeliveryOrderCreated($so, $do);
        }
    }

    /**
     * Log status perubahan DO.
     */
    protected static function log(
        DeliveryOrder $do,
        ?string $from,
        string $to,
        ?string $notes = null
    ): void {
        DeliveryOrderStatusLog::create([
            'delivery_order_id' => $do->id,
            'from_status'       => $from,
            'to_status'         => $to,
            'notes'             => $notes,
            'changed_by'        => auth()->id(),
        ]);
    }

    /**
     * Hitung sisa qty yang belum terkirim untuk 1 SO item.
     */
    public static function getRemainingQty(SalesOrderItem $soItem, ?int $excludeDoId = null): float
    {
        $query = DeliveryOrderItem::where('sales_order_item_id', $soItem->id)
            ->whereHas('deliveryOrder', function ($q) {
                $q->whereNotIn('status', ['cancelled']);
            });

        if ($excludeDoId) {
            $query->where('delivery_order_id', '!=', $excludeDoId);
        }

        $alreadyDelivered = $query->sum('qty_delivered');

        return (float) ($soItem->qty - $alreadyDelivered);
    }
}