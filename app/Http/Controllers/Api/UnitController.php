<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use RuntimeException;

class UnitController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(Unit::class, 'unit');
    }

    public function index()
    {
        return $this->paginated(Unit::query()->latest()->paginate(15), 'Unidades listadas correctamente.');
    }

    public function store(UnitRequest $request)
    {
        try {
            $unit = Unit::create($request->validated());

            return $this->success($unit, 'Unidad creada correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Unit $unit)
    {
        return $this->success($unit->load('products'), 'Unidad cargada correctamente.');
    }

    public function update(UnitRequest $request, Unit $unit)
    {
        try {
            $unit->update($request->validated());

            return $this->success($unit->refresh(), 'Unidad actualizada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Unit $unit)
    {
        try {
            $unit->delete();

            return $this->success([], 'Unidad eliminada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
