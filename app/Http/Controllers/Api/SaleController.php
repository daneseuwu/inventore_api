<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\SaleIndexRequest;
use App\Http\Requests\SaleRequest;
use App\Models\Sale;
use App\Services\Inventory\SaleService;
use RuntimeException;

class SaleController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(Sale::class, 'sale');
    }

    public function index(SaleIndexRequest $request)
    {
        $query = Sale::query()
            ->with(['customer', 'warehouse', 'details.product'])
            ->when($request->validated('customer_id'), fn ($query, $customerId) => $query->where('customer_id', $customerId))
            ->when($request->validated('warehouse_id'), fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->validated('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest();

        return $this->paginated($query->paginate($request->integer('per_page', 15)), 'Ventas listadas correctamente.');
    }

    public function store(SaleRequest $request, SaleService $saleService)
    {
        try {
            $sale = $saleService->createDraft($request->validated());

            return $this->success($sale, 'Venta creada correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Sale $sale)
    {
        return $this->success($sale->load(['customer', 'warehouse', 'details.product']), 'Venta cargada correctamente.');
    }

    public function update(SaleRequest $request, Sale $sale, SaleService $saleService)
    {
        try {
            $sale = $saleService->updateDraft($sale, $request->validated());

            return $this->success($sale, 'Venta actualizada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Sale $sale)
    {
        try {
            $sale->delete();

            return $this->success([], 'Venta eliminada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function confirm(Sale $sale, SaleService $saleService)
    {
        try {
            $this->authorize('confirm', $sale);

            $sale = $saleService->confirm($sale, request()->user());

            return $this->success($sale, 'Venta confirmada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
