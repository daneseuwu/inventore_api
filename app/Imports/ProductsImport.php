<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use RuntimeException;

class ProductsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected array $summary = [
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'errors' => [],
    ];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->normalizeRow($row->toArray());
            $this->summary['processed']++;

            try {
                $validated = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'description' => ['nullable', 'string'],
                    'sku' => ['required', 'string', 'max:255'],
                    'barcode' => ['nullable', 'string', 'max:255'],
                    'price' => ['required', 'numeric', 'min:0'],
                    'cost' => ['required', 'numeric', 'min:0'],
                    'stock_min' => ['required', 'integer', 'min:0'],
                    'stock_max' => ['required', 'integer', 'min:0'],
                    'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                    'category_name' => ['nullable', 'string', 'max:255'],
                    'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
                    'brand_name' => ['nullable', 'string', 'max:255'],
                    'unit_id' => ['nullable', 'integer', 'exists:units,id'],
                    'unit_name' => ['nullable', 'string', 'max:255'],
                    'status' => ['nullable', Rule::in(['active', 'inactive'])],
                ])->after(function ($validator) use ($data): void {
                    if (($data['stock_max'] ?? 0) < ($data['stock_min'] ?? 0)) {
                        $validator->errors()->add('stock_max', 'stock_max debe ser mayor o igual a stock_min.');
                    }
                })->validate();

                DB::transaction(function () use ($validated): void {
                    $category = $this->resolveCategory($validated);
                    $brand = $this->resolveBrand($validated);
                    $unit = $this->resolveUnit($validated);

                    $product = Product::withTrashed()->firstWhere('sku', $validated['sku']);

                    $payload = [
                        'name' => $validated['name'],
                        'description' => $validated['description'] ?? null,
                        'sku' => $validated['sku'],
                        'barcode' => $validated['barcode'] ?? null,
                        'price' => $validated['price'],
                        'cost' => $validated['cost'],
                        'stock_min' => $validated['stock_min'],
                        'stock_max' => $validated['stock_max'],
                        'category_id' => $category?->getKey(),
                        'brand_id' => $brand?->getKey(),
                        'unit_id' => $unit?->getKey(),
                        'status' => $validated['status'] ?? 'active',
                    ];

                    if ($product) {
                        if ($product->trashed()) {
                            $product->restore();
                        }

                        $product->update($payload);
                        $this->summary['updated']++;

                        return;
                    }

                    Product::create($payload);
                    $this->summary['created']++;
                });
            } catch (\Throwable $e) {
                $this->summary['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    public function result(): array
    {
        return $this->summary;
    }

    protected function normalizeRow(array $data): array
    {
        foreach ([
            'name',
            'description',
            'sku',
            'barcode',
            'category_name',
            'brand_name',
            'unit_name',
            'status',
        ] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $data[$field] = trim((string) $data[$field]);
            }
        }

        return $data;
    }

    protected function resolveCategory(array $data): ?Category
    {
        if (! empty($data['category_id'])) {
            return Category::query()->findOrFail($data['category_id']);
        }

        if (! empty($data['category_name'])) {
            return Category::firstOrCreate(['name' => $data['category_name']]);
        }

        return null;
    }

    protected function resolveBrand(array $data): ?Brand
    {
        if (! empty($data['brand_id'])) {
            return Brand::query()->findOrFail($data['brand_id']);
        }

        if (! empty($data['brand_name'])) {
            return Brand::firstOrCreate(['name' => $data['brand_name']]);
        }

        return null;
    }

    protected function resolveUnit(array $data): ?Unit
    {
        if (! empty($data['unit_id'])) {
            return Unit::query()->findOrFail($data['unit_id']);
        }

        if (! empty($data['unit_name'])) {
            return Unit::firstOrCreate(
                ['name' => $data['unit_name']],
                ['abbreviation' => strtolower(substr($data['unit_name'], 0, 3))]
            );
        }

        return null;
    }
}
