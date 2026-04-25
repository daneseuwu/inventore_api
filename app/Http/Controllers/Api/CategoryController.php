<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use RuntimeException;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(Category::class, 'category');
    }

    public function index()
    {
        return $this->paginated(Category::query()->latest()->paginate(15), 'Categorías listadas correctamente.');
    }

    public function store(CategoryRequest $request)
    {
        try {
            $category = Category::create($request->validated());

            return $this->success($category, 'Categoría creada correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Category $category)
    {
        return $this->success($category->load('products'), 'Categoría cargada correctamente.');
    }

    public function update(CategoryRequest $request, Category $category)
    {
        try {
            $category->update($request->validated());

            return $this->success($category->refresh(), 'Categoría actualizada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Category $category)
    {
        try {
            $category->delete();

            return $this->success([], 'Categoría eliminada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
