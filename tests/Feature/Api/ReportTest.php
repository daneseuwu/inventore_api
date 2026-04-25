<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Unit;
use App\Models\Warehouse;
use Laravel\Sanctum\Sanctum;

it('lists products with low stock without database errors', function (): void {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $category = Category::create([
        'name' => 'Electronica',
        'description' => 'Categoria de prueba',
    ]);

    $brand = Brand::create([
        'name' => 'DemoBrand',
    ]);

    $unit = Unit::create([
        'name' => 'Unidad',
        'abbreviation' => 'und',
    ]);

    $warehouse = Warehouse::create([
        'name' => 'Central',
        'location' => 'San Salvador',
    ]);

    $lowStockProduct = Product::create([
        'name' => 'Mouse Pro',
        'description' => 'Producto con poco stock',
        'sku' => 'MOU-001',
        'barcode' => '750000000100',
        'price' => 25,
        'cost' => 10,
        'stock_min' => 5,
        'stock_max' => 50,
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'unit_id' => $unit->id,
        'status' => 'active',
    ]);

    Stock::create([
        'product_id' => $lowStockProduct->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 2,
    ]);

    $response = $this->getJson('/api/v1/reports/low-stock?per_page=15');

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Productos con bajo stock listados correctamente.',
        ])
        ->assertJsonFragment([
            'id' => $lowStockProduct->id,
            'name' => 'Mouse Pro',
            'total_stock' => 2,
        ]);
});
