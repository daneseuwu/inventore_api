<?php

namespace App\Services\Inventory;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportService
{
    public function kardex(Product $product, ?Warehouse $warehouse = null): Collection
    {
        $query = StockMovement::query()
            ->with(['warehouse', 'user', 'reference'])
            ->where('product_id', $product->getKey())
            ->orderBy('created_at')
            ->orderBy('id');

        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->getKey());
        }

        $balance = 0;

        return $query->get()->map(function (StockMovement $movement) use (&$balance): array {
            $balance += $movement->type === 'OUT' ? -$movement->quantity : $movement->quantity;

            return [
                'id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'balance' => $balance,
                'warehouse' => $movement->warehouse?->only(['id', 'name']),
                'user' => $movement->user?->only(['id', 'name', 'email']),
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'created_at' => $movement->created_at,
            ];
        });
    }

    public function lowStock(?int $warehouseId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'brand', 'unit', 'stocks.warehouse'])
            ->select('products.*')
            ->selectSub(
                function ($subQuery) use ($warehouseId): void {
                    $subQuery->from('stocks')
                        ->selectRaw('COALESCE(SUM(stocks.quantity), 0)')
                        ->whereColumn('stocks.product_id', 'products.id');

                    if ($warehouseId) {
                        $subQuery->where('stocks.warehouse_id', $warehouseId);
                    }
                },
                'total_stock'
            )
            ->whereRaw(
                $warehouseId
                    ? 'COALESCE((select SUM(stocks.quantity) from stocks where stocks.product_id = products.id and stocks.warehouse_id = ?), 0) <= products.stock_min'
                    : 'COALESCE((select SUM(stocks.quantity) from stocks where stocks.product_id = products.id), 0) <= products.stock_min',
                $warehouseId ? [$warehouseId] : []
            )
            ->orderBy('name');

        return $query->paginate($perPage);
    }

    public function mostSold(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.barcode',
                'products.price',
                'products.cost',
                'products.stock_min',
                'products.stock_max',
                'products.category_id',
                'products.brand_id',
                'products.unit_id',
                'products.status',
            ])
            ->join('sale_details', 'sale_details.product_id', '=', 'products.id')
            ->groupBy([
                'products.id',
                'products.name',
                'products.sku',
                'products.barcode',
                'products.price',
                'products.cost',
                'products.stock_min',
                'products.stock_max',
                'products.category_id',
                'products.brand_id',
                'products.unit_id',
                'products.status',
            ])
            ->selectRaw('SUM(sale_details.quantity) as total_sold')
            ->orderByDesc('total_sold')
            ->paginate($perPage);
    }
}
