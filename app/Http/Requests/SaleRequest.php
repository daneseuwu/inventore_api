<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Sale|null $sale */
        $sale = $this->route('sale');

        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'status' => ['nullable', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
