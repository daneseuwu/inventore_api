<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportKardexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ];
    }
}
