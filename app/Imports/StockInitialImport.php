<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use RuntimeException;

class StockInitialImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected array $summary = [
        'processed' => 0,
        'movements_created' => 0,
        'errors' => [],
    ];

    public function __construct(
        protected StockService $stockService,
        protected $user
    ) {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->normalizeRow($row->toArray());
            $this->summary['processed']++;

            try {
                $validated = Validator::make($data, [
                    'product_id' => ['nullable', 'integer', 'exists:products,id'],
                    'sku' => ['nullable', 'string', 'max:255'],
                    'barcode' => ['nullable', 'string', 'max:255'],
                    'product_name' => ['nullable', 'string', 'max:255'],
                    'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
                    'warehouse_name' => ['nullable', 'string', 'max:255'],
                    'quantity' => ['required', 'integer', 'min:1'],
                ])->validate();

                [$product, $warehouse] = $this->resolveModels($validated);

                $this->stockService->increase(
                    $product,
                    $warehouse,
                    (int) $validated['quantity'],
                    $this->user,
                    'stock_initial_import',
                    null
                );

                $this->summary['movements_created']++;
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
            'product_id',
            'sku',
            'barcode',
            'product_name',
            'warehouse_id',
            'warehouse_name',
        ] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $data[$field] = trim((string) $data[$field]);
            }
        }

        return $data;
    }

    protected function resolveModels(array $data): array
    {
        $product = null;

        if (! empty($data['product_id'])) {
            $product = Product::query()->find($data['product_id']);
        } elseif (! empty($data['sku'])) {
            $product = Product::query()->where('sku', $data['sku'])->first();
        } elseif (! empty($data['barcode'])) {
            $product = Product::query()->where('barcode', $data['barcode'])->first();
        } elseif (! empty($data['product_name'])) {
            $product = Product::query()->where('name', $data['product_name'])->first();
        }

        $warehouse = null;

        if (! empty($data['warehouse_id'])) {
            $warehouse = Warehouse::query()->find($data['warehouse_id']);
        } elseif (! empty($data['warehouse_name'])) {
            $warehouse = Warehouse::query()->where('name', $data['warehouse_name'])->first();
        }

        if (! $product) {
            throw new RuntimeException('Producto no encontrado en la fila importada.');
        }

        if (! $warehouse) {
            throw new RuntimeException('Almacén no encontrado en la fila importada.');
        }

        return [$product, $warehouse];
    }
}
