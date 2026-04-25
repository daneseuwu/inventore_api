<?php

namespace App\Exports;

use App\Models\Stock;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockInitialExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Stock::query()
            ->with(['product', 'warehouse'])
            ->orderBy('warehouse_id')
            ->orderBy('product_id');
    }

    public function headings(): array
    {
        return [
            'stock_id',
            'product_sku',
            'product_barcode',
            'product_name',
            'warehouse_name',
            'quantity',
        ];
    }

    public function map($stock): array
    {
        return [
            $stock->id,
            $stock->product?->sku,
            $stock->product?->barcode,
            $stock->product?->name,
            $stock->warehouse?->name,
            $stock->quantity,
        ];
    }
}
