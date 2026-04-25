<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ImportExportController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('login', function () {
        return response()->json([
            'success' => false,
            'message' => 'Usa POST /api/v1/auth/login para autenticarte.',
            'data' => [
                'login_endpoint' => '/api/v1/auth/login',
                'method' => 'POST',
            ],
        ]);
    });

    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/profile', [AuthController::class, 'profile']);

        Route::apiResource('users', UserController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('brands', BrandController::class);
        Route::apiResource('units', UnitController::class);
        Route::apiResource('warehouses', WarehouseController::class);
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('stocks', StockController::class)->only(['index', 'show']);
        Route::apiResource('stock-movements', StockMovementController::class)->parameters([
            'stock-movements' => 'stock_movement',
        ])->only(['index', 'show', 'store']);
        Route::apiResource('purchases', PurchaseController::class);
        Route::apiResource('sales', SaleController::class);
        Route::apiResource('transfers', TransferController::class);

        Route::post('purchases/{purchase}/confirm', [PurchaseController::class, 'confirm']);
        Route::post('sales/{sale}/confirm', [SaleController::class, 'confirm']);
        Route::post('transfers/{transfer}/confirm', [TransferController::class, 'confirm']);

        Route::get('exports/products', [ImportExportController::class, 'exportProducts']);
        Route::get('exports/catalogs/{catalog}', [ImportExportController::class, 'exportCatalog']);
        Route::get('exports/stock-initial', [ImportExportController::class, 'exportStockInitial']);

        Route::post('imports/products', [ImportExportController::class, 'importProducts']);
        Route::post('imports/catalogs/{catalog}', [ImportExportController::class, 'importCatalog']);
        Route::post('imports/stock-initial', [ImportExportController::class, 'importStockInitial']);

        Route::get('reports/kardex/{product}', [ReportController::class, 'kardex']);
        Route::get('reports/low-stock', [ReportController::class, 'lowStock']);
        Route::get('reports/most-sold', [ReportController::class, 'mostSold']);
    });
});
