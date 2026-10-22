<?php

namespace App\Services;

use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\Sales\DeliveryOrder;
use Illuminate\Support\Facades\DB;

class SalesInvoiceService
{
    /**
     * Generate nomor invoice unik per bulan.
     * Format: INV-YYYYMM-XXXX
     */
    public static function generateNumber(): string
    {
        $prefix = 'INV-' . date('Ym') . '-';

        $last = SalesInvoice::where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->first();

        $lastNumber = $last ? (int) substr($last->invoice_number, -4) : 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Buat Invoice baru dari Delivery Order.
     */
    public static function createFromDeliveryOrder(DeliveryOrder $do, array $data = []): SalesInvoice
    {
        if ($do->status === 'cancelled') {
            throw new \Exception('Tidak bisa membuat invoice dari DO yang dibatalkan.');
        }

        // Cek apakah DO ini sudah pernah dibuatkan invoice
        $existingInvoice = SalesInvoice::where('delivery_order_id', $do->id)
            ->where('status', '!=', 'cancelled')
            ->first();
            
        if ($existingInvoice) {
            throw new \Exception("Delivery Order ini sudah memiliki Invoice ({$existingInvoice->invoice_number}).");
        }

        DB::beginTransaction();
        try {
            $do->load('items.product', 'customer');

            $subtotal = 0;
            
            // 1. Buat header Invoice
            $invoice = SalesInvoice::create([
                'invoice_number'    => self::generateNumber(),
                'delivery_order_id' => $do->id,
                'customer_id'       => $do->customer_id,
                'invoice_date'      => $data['invoice_date'] ?? now()->toDateString(),
                'due_date'          => $data['due_date'] ?? now()->addDays(30)->toDateString(),
                'status'            => 'unpaid',
                'subtotal'          => 0, // Akan diupdate setelah items
                'discount_amount'   => 0,
                'tax_amount'        => 0,
                'total_amount'      => 0,
                'paid_amount'       => 0,
                'notes'             => $data['notes'] ?? null,
                'created_by'        => auth()->id(),
            ]);

            // 2. Buat items
            foreach ($do->items as $doItem) {
                $qty = (float) $doItem->qty_delivered;
                $price = (float) $doItem->salesOrderItem->unit_price ?? 0;
                $itemSubtotal = $qty * $price;
                $subtotal += $itemSubtotal;

                SalesInvoiceItem::create([
                    'sales_invoice_id'       => $invoice->id,
                    'delivery_order_item_id' => $doItem->id,
                    'product_id'             => $doItem->product_id,
                    'description'            => $doItem->product->nama ?? '-',
                    'qty'                    => $qty,
                    'unit_price'             => $price,
                    'discount_percent'       => 0,
                    'discount_amount'        => 0,
                    'subtotal'               => $itemSubtotal,
                ]);
            }

            // 3. Update total di header Invoice
            $invoice->update([
                'subtotal'     => $subtotal,
                'total_amount' => $subtotal,
            ]);

            DB::commit();

            return $invoice->fresh(['items', 'customer', 'deliveryOrder']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Tandai Invoice Lunas (Paid).
     */
    public static function markAsPaid(SalesInvoice $invoice, ?string $notes = null): SalesInvoice
    {
        if ($invoice->isPaid()) {
            throw new \Exception('Invoice ini sudah lunas.');
        }

        DB::beginTransaction();
        try {
            $invoice->update([
                'status'      => 'paid',
                'paid_amount' => $invoice->total_amount,
                'paid_at'     => now(),
            ]);
            
            // Opsional: Bisa tambahkan log activity di sini

            DB::commit();

            return $invoice->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}