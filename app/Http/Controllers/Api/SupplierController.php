<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use RuntimeException;

class SupplierController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(Supplier::class, 'supplier');
    }

    public function index()
    {
        return $this->paginated(Supplier::query()->latest()->paginate(15), 'Proveedores listados correctamente.');
    }

    public function store(SupplierRequest $request)
    {
        try {
            $supplier = Supplier::create($request->validated());

            return $this->success($supplier, 'Proveedor creado correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Supplier $supplier)
    {
        return $this->success($supplier->load('purchases'), 'Proveedor cargado correctamente.');
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        try {
            $supplier->update($request->validated());

            return $this->success($supplier->refresh(), 'Proveedor actualizado correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Supplier $supplier)
    {
        try {
            $supplier->delete();

            return $this->success([], 'Proveedor eliminado correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
