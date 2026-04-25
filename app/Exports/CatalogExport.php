<?php

namespace App\Exports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CatalogExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected string $catalog
    ) {
    }

    public function query()
    {
        return match ($this->catalog) {
            'categories' => Category::query()->orderBy('name'),
            'brands' => Brand::query()->orderBy('name'),
            'units' => Unit::query()->orderBy('name'),
            'warehouses' => Warehouse::query()->orderBy('name'),
            'suppliers' => Supplier::query()->orderBy('name'),
            'customers' => Customer::query()->orderBy('name'),
            default => throw new InvalidArgumentException('Catálogo inválido.'),
        };
    }

    public function headings(): array
    {
        return match ($this->catalog) {
            'categories' => ['id', 'name', 'description'],
            'brands' => ['id', 'name'],
            'units' => ['id', 'name', 'abbreviation'],
            'warehouses' => ['id', 'name', 'location'],
            'suppliers' => ['id', 'name', 'email', 'phone', 'address'],
            'customers' => ['id', 'name', 'email', 'phone'],
            default => throw new InvalidArgumentException('Catálogo inválido.'),
        };
    }

    public function map($model): array
    {
        return match ($this->catalog) {
            'categories' => [$model->id, $model->name, $model->description],
            'brands' => [$model->id, $model->name],
            'units' => [$model->id, $model->name, $model->abbreviation],
            'warehouses' => [$model->id, $model->name, $model->location],
            'suppliers' => [$model->id, $model->name, $model->email, $model->phone, $model->address],
            'customers' => [$model->id, $model->name, $model->email, $model->phone],
            default => throw new InvalidArgumentException('Catálogo inválido.'),
        };
    }
}
