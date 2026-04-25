<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\ReportKardexRequest;
use App\Http\Requests\ReportLowStockRequest;
use App\Http\Requests\ReportMostSoldRequest;
use App\Models\Product;
use App\Services\Inventory\ReportService;

class ReportController extends Controller
{
    use ApiResponse;

    public function kardex(ReportKardexRequest $request, Product $product, ReportService $reportService)
    {
        $warehouse = $request->filled('warehouse_id')
            ? \App\Models\Warehouse::findOrFail($request->integer('warehouse_id'))
            : null;

        return $this->success([
            'product' => $product->load(['category', 'brand', 'unit']),
            'movements' => $reportService->kardex($product, $warehouse),
        ], 'Kardex generado correctamente.');
    }

    public function lowStock(ReportLowStockRequest $request, ReportService $reportService)
    {
        $stocks = $reportService->lowStock(
            $request->filled('warehouse_id') ? $request->integer('warehouse_id') : null,
            $request->integer('per_page', 15)
        );

        return $this->paginated($stocks, 'Productos con bajo stock listados correctamente.');
    }

    public function mostSold(ReportMostSoldRequest $request, ReportService $reportService)
    {
        return $this->paginated(
            $reportService->mostSold($request->integer('per_page', 15)),
            'Productos más vendidos listados correctamente.'
        );
    }
}
