<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\StockMovementIndexRequest;
use App\Http\Requests\StockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use RuntimeException;

class StockMovementController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(StockMovement::class, 'stock_movement');
    }

    public function index(StockMovementIndexRequest $request)
    {
        $query = StockMovement::query()
            ->with(['product', 'warehouse', 'user', 'reference'])
            ->when($request->validated('product_id'), fn ($query, $productId) => $query->where('product_id', $productId))
            ->when($request->validated('warehouse_id'), fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->validated('user_id'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($request->validated('type'), fn ($query, $type) => $query->where('type', $type))
            ->latest();

        return $this->paginated(
            $query->paginate($request->integer('per_page', 15)),
            'Movimientos de stock listados correctamente.'
        );
    }

    public function show(StockMovement $stock_movement)
    {
        return $this->success($stock_movement->load(['product', 'warehouse', 'user', 'reference']), 'Movimiento de stock cargado correctamente.');
    }

    public function store(StockMovementRequest $request, StockService $stockService)
    {
        try {
            $product = Product::findOrFail($request->validated('product_id'));
            $warehouse = Warehouse::findOrFail($request->validated('warehouse_id'));

            $movement = $stockService->manualMovement(
                $product,
                $warehouse,
                $request->validated('type'),
                $request->validated('quantity'),
                $request->user(),
                $request->validated('reference_type'),
                $request->validated('reference_id')
            );

            return $this->success($movement->load(['product', 'warehouse', 'user', 'reference']), 'Movimiento registrado correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
