<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockIndexRequest extends FormRequest
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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
