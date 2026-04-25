<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Purchase|null $purchase */
        $purchase = $this->route('purchase');

        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'status' => ['nullable', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
