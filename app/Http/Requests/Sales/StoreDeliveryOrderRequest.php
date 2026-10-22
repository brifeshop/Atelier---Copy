<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create delivery-orders');
    }

    public function rules(): array
    {
        return [
            'sales_order_id' => [
                'required',
                'integer',
                Rule::exists('sales_orders', 'id')
                    ->whereNotIn('status', ['cancelled', 'closed']),
            ],
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id'),
            ],
            'delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'shipping_address' => ['nullable', 'string', 'max:1000'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_order_item_id' => [
                'required',
                'integer',
                Rule::exists('sales_order_items', 'id'),
            ],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id'),
            ],
            'items.*.location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where('warehouse_id', $this->input('warehouse_id')),
            ],
            'items.*.qty_ordered' => ['required', 'numeric', 'min:0.01'],
            'items.*.qty_delivered' => ['required', 'numeric', 'min:0.01', 'lte:items.*.qty_ordered'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'sales_order_id.required' => 'Sales Order wajib dipilih.',
            'sales_order_id.exists' => 'Sales Order tidak ditemukan atau sudah dibatalkan.',
            'warehouse_id.required' => 'Gudang asal wajib dipilih.',
            'warehouse_id.exists' => 'Gudang tidak ditemukan.',
            'delivery_date.required' => 'Tanggal pengiriman wajib diisi.',
            'delivery_date.after_or_equal' => 'Tanggal pengiriman tidak boleh sebelum hari ini.',
            'items.required' => 'Minimal 1 item harus diisi.',
            'items.min' => 'Minimal 1 item harus diisi.',
            'items.*.sales_order_item_id.required' => 'Item Sales Order wajib dipilih.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.location_id.required' => 'Lokasi rak wajib dipilih.',
            'items.*.location_id.exists' => 'Lokasi rak tidak valid untuk gudang ini.',
            'items.*.qty_ordered.required' => 'Qty ordered wajib diisi.',
            'items.*.qty_delivered.required' => 'Qty dikirim wajib diisi.',
            'items.*.qty_delivered.min' => 'Qty dikirim minimal 0.01.',
            'items.*.qty_delivered.lte' => 'Qty dikirim tidak boleh melebihi qty ordered.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                $soItem = \App\Models\Sales\SalesOrderItem::find($item['sales_order_item_id'] ?? null);

                if (!$soItem) {
                    continue;
                }

                $alreadyDelivered = \App\Models\Sales\DeliveryOrderItem::where('sales_order_item_id', $soItem->id)
                    ->whereHas('deliveryOrder', function ($q) {
                        $q->whereNotIn('status', ['cancelled']);
                    })
                    ->sum('qty_delivered');

                $remaining = $soItem->qty - $alreadyDelivered;
                $requested = (float) ($item['qty_delivered'] ?? 0);

                if ($requested > $remaining) {
                    $validator->errors()->add(
                        "items.{$index}.qty_delivered",
                        "Qty dikirim ({$requested}) melebihi sisa yang belum terkirim ({$remaining})."
                    );
                }
            }
        });
    }
}