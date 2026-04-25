<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use RuntimeException;

class BrandController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(Brand::class, 'brand');
    }

    public function index()
    {
        return $this->paginated(Brand::query()->latest()->paginate(15), 'Marcas listadas correctamente.');
    }

    public function store(BrandRequest $request)
    {
        try {
            $brand = Brand::create($request->validated());

            return $this->success($brand, 'Marca creada correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Brand $brand)
    {
        return $this->success($brand->load('products'), 'Marca cargada correctamente.');
    }

    public function update(BrandRequest $request, Brand $brand)
    {
        try {
            $brand->update($request->validated());

            return $this->success($brand->refresh(), 'Marca actualizada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Brand $brand)
    {
        try {
            $brand->delete();

            return $this->success([], 'Marca eliminada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
