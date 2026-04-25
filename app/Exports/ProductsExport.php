<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Product::query()
            ->with(['category', 'brand', 'unit'])
            ->withSum('stocks as total_stock', 'quantity')
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'description',
            'sku',
            'barcode',
            'price',
            'cost',
            'stock_min',
            'stock_max',
            'category_name',
            'brand_name',
            'unit_name',
            'status',
            'total_stock',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->description,
            $product->sku,
            $product->barcode,
            $product->price,
            $product->cost,
            $product->stock_min,
            $product->stock_max,
            $product->category?->name,
            $product->brand?->name,
            $product->unit?->name,
            (string) $product->status,
            (int) ($product->total_stock ?? 0),
        ];
    }
}
