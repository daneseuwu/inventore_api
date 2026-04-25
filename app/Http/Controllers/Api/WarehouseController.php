<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\WarehouseRequest;
use App\Models\Warehouse;
use RuntimeException;

class WarehouseController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(Warehouse::class, 'warehouse');
    }

    public function index()
    {
        return $this->paginated(Warehouse::query()->latest()->paginate(15), 'Almacenes listados correctamente.');
    }

    public function store(WarehouseRequest $request)
    {
        try {
            $warehouse = Warehouse::create($request->validated());

            return $this->success($warehouse, 'Almacén creado correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Warehouse $warehouse)
    {
        return $this->success($warehouse->load(['stocks.product']), 'Almacén cargado correctamente.');
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        try {
            $warehouse->update($request->validated());

            return $this->success($warehouse->refresh(), 'Almacén actualizado correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Warehouse $warehouse)
    {
        try {
            $warehouse->delete();

            return $this->success([], 'Almacén eliminado correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
