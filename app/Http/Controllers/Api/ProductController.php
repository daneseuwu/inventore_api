<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use RuntimeException;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(ProductIndexRequest $request)
    {
        $query = Product::query()
            ->with(['category', 'brand', 'unit'])
            ->when($request->validated('search'), function ($query, string $search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->validated('category_id'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($request->validated('brand_id'), fn ($query, $brandId) => $query->where('brand_id', $brandId))
            ->when($request->validated('unit_id'), fn ($query, $unitId) => $query->where('unit_id', $unitId))
            ->when($request->validated('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name');

        return $this->paginated($query->paginate($request->integer('per_page', 15)), 'Productos listados correctamente.');
    }

    public function store(ProductRequest $request)
    {
        try {
            $product = Product::create($request->validated());

            return $this->success($product->load(['category', 'brand', 'unit']), 'Producto creado correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Product $product)
    {
        return $this->success($product->load(['category', 'brand', 'unit', 'stocks.warehouse', 'stockMovements.warehouse']), 'Producto cargado correctamente.');
    }

    public function update(ProductRequest $request, Product $product)
    {
        try {
            $product->update($request->validated());

            return $this->success($product->refresh()->load(['category', 'brand', 'unit']), 'Producto actualizado correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Product $product)
    {
        try {
            $product->delete();

            return $this->success([], 'Producto eliminado correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
