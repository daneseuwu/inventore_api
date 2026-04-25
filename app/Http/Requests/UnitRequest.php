<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Unit|null $unit */
        $unit = $this->route('unit');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units', 'name')->ignore($unit?->getKey()),
            ],
            'abbreviation' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'abbreviation')->ignore($unit?->getKey()),
            ],
        ];
    }
}
