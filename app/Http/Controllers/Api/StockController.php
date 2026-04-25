<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\StockIndexRequest;
use App\Models\Stock;

class StockController extends Controller
{
    use ApiResponse;

    public function index(StockIndexRequest $request)
    {
        $query = Stock::query()
            ->with(['product.category', 'product.brand', 'product.unit', 'warehouse'])
            ->when($request->validated('product_id'), fn ($query, $productId) => $query->where('product_id', $productId))
            ->when($request->validated('warehouse_id'), fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->orderBy('product_id');

        return $this->paginated($query->paginate($request->integer('per_page', 15)), 'Stock listado correctamente.');
    }

    public function show(Stock $stock)
    {
        return $this->success($stock->load(['product.category', 'product.brand', 'product.unit', 'warehouse']), 'Stock cargado correctamente.');
    }
}
