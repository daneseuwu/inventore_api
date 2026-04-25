<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\PurchaseIndexRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use App\Services\Inventory\PurchaseService;
use RuntimeException;

class PurchaseController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(Purchase::class, 'purchase');
    }

    public function index(PurchaseIndexRequest $request)
    {
        $query = Purchase::query()
            ->with(['supplier', 'warehouse', 'details.product'])
            ->when($request->validated('supplier_id'), fn ($query, $supplierId) => $query->where('supplier_id', $supplierId))
            ->when($request->validated('warehouse_id'), fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->validated('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest();

        return $this->paginated($query->paginate($request->integer('per_page', 15)), 'Compras listadas correctamente.');
    }

    public function store(PurchaseRequest $request, PurchaseService $purchaseService)
    {
        try {
            $purchase = $purchaseService->createDraft($request->validated());

            return $this->success($purchase, 'Compra creada correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Purchase $purchase)
    {
        return $this->success($purchase->load(['supplier', 'warehouse', 'details.product']), 'Compra cargada correctamente.');
    }

    public function update(PurchaseRequest $request, Purchase $purchase, PurchaseService $purchaseService)
    {
        try {
            $purchase = $purchaseService->updateDraft($purchase, $request->validated());

            return $this->success($purchase, 'Compra actualizada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Purchase $purchase)
    {
        try {
            $purchase->delete();

            return $this->success([], 'Compra eliminada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function confirm(Purchase $purchase, PurchaseService $purchaseService)
    {
        try {
            $this->authorize('confirm', $purchase);

            $purchase = $purchaseService->confirm($purchase, request()->user());

            return $this->success($purchase, 'Compra confirmada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
