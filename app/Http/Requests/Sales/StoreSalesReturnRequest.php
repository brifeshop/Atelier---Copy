<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Bisa disesuaikan dengan permission nanti
    }

    public function rules(): array
    {
        return [
            'delivery_order_id' => ['required', 'integer', Rule::exists('delivery_orders', 'id')],
            'return_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'in:damaged,wrong_item,expired,other'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.delivery_order_item_id' => ['required', 'integer', Rule::exists('delivery_order_items', 'id')],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_order_id.required' => 'Pilih Delivery Order terlebih dahulu.',
            'reason.required' => 'Alasan retur wajib dipilih.',
            'items.required' => 'Minimal 1 barang harus diretur.',
            'items.*.location_id.required' => 'Lokasi tujuan (gudang rusak/reject) wajib dipilih.',
            'items.*.qty.min' => 'Qty retur minimal 0.01.',
        ];
    }
}