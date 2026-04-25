<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'type' => ['nullable', Rule::in(['IN', 'OUT'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
