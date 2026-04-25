<?php

namespace App\Http\Requests;

use App\Models\Transfer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Transfer|null $transfer */
        $transfer = $this->route('transfer');

        return [
            'from_warehouse_id' => ['required', 'different:to_warehouse_id', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'status' => ['nullable', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
