<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use RuntimeException;

class CatalogImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected array $summary = [
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'errors' => [],
    ];

    public function __construct(
        protected string $catalog
    ) {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->normalizeRow($row->toArray());
            $this->summary['processed']++;

            try {
                $validated = Validator::make($data, $this->rules())->validate();

                DB::transaction(function () use ($validated): void {
                    $model = $this->resolveModel($validated);
                    $payload = $this->payload($validated);

                    if ($model) {
                        if (method_exists($model, 'trashed') && $model->trashed()) {
                            $model->restore();
                        }

                        $model->update($payload);
                        $this->summary['updated']++;

                        return;
                    }

                    $this->createModel($payload);
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
            'abbreviation',
            'email',
            'phone',
            'address',
            'location',
        ] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $data[$field] = trim((string) $data[$field]);
            }
        }

        return $data;
    }

    protected function rules(): array
    {
        return match ($this->catalog) {
            'categories' => [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
            ],
            'brands' => [
                'name' => ['required', 'string', 'max:255'],
            ],
            'units' => [
                'name' => ['required', 'string', 'max:255'],
                'abbreviation' => ['required', 'string', 'max:20'],
            ],
            'warehouses' => [
                'name' => ['required', 'string', 'max:255'],
                'location' => ['nullable', 'string'],
            ],
            'suppliers' => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string'],
            ],
            'customers' => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
            ],
            default => throw new RuntimeException('Catálogo inválido.'),
        };
    }

    protected function resolveModel(array $data)
    {
        return match ($this->catalog) {
            'categories' => Category::withTrashed()->firstWhere('name', $data['name']),
            'brands' => Brand::withTrashed()->firstWhere('name', $data['name']),
            'units' => Unit::withTrashed()->firstWhere('abbreviation', $data['abbreviation'])
                ?? Unit::withTrashed()->firstWhere('name', $data['name']),
            'warehouses' => Warehouse::withTrashed()->firstWhere('name', $data['name']),
            'suppliers' => ! empty($data['email'])
                ? Supplier::withTrashed()->firstWhere('email', $data['email'])
                : Supplier::withTrashed()->firstWhere('name', $data['name']),
            'customers' => ! empty($data['email'])
                ? Customer::withTrashed()->firstWhere('email', $data['email'])
                : Customer::withTrashed()->firstWhere('name', $data['name']),
            default => throw new RuntimeException('Catálogo inválido.'),
        };
    }

    protected function payload(array $data): array
    {
        return match ($this->catalog) {
            'categories' => [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ],
            'brands' => [
                'name' => $data['name'],
            ],
            'units' => [
                'name' => $data['name'],
                'abbreviation' => $data['abbreviation'],
            ],
            'warehouses' => [
                'name' => $data['name'],
                'location' => $data['location'] ?? null,
            ],
            'suppliers' => [
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ],
            'customers' => [
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ],
            default => throw new RuntimeException('Catálogo inválido.'),
        };
    }

    protected function createModel(array $payload): void
    {
        match ($this->catalog) {
            'categories' => Category::create($payload),
            'brands' => Brand::create($payload),
            'units' => Unit::create($payload),
            'warehouses' => Warehouse::create($payload),
            'suppliers' => Supplier::create($payload),
            'customers' => Customer::create($payload),
            default => throw new RuntimeException('Catálogo inválido.'),
        };
    }
}
