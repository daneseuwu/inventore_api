<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Laravel\Sanctum\Sanctum;

it('handles purchase sale and transfer stock flows safely', function (): void {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $category = Category::create([
        'name' => 'Tecnologia',
        'description' => 'Productos tecnológicos',
    ]);

    $brand = Brand::create([
        'name' => 'Acme',
    ]);

    $unit = Unit::create([
        'name' => 'Unidad',
        'abbreviation' => 'und',
    ]);

    $mainWarehouse = Warehouse::create([
        'name' => 'Central',
        'location' => 'San Salvador',
    ]);

    $secondaryWarehouse = Warehouse::create([
        'name' => 'Sucursal',
        'location' => 'Santa Tecla',
    ]);

    $supplier = Supplier::create([
        'name' => 'Proveedor Demo',
        'email' => 'proveedor@example.com',
        'phone' => '2222-3333',
        'address' => 'Dirección de prueba',
    ]);

    $customer = Customer::create([
        'name' => 'Cliente Demo',
        'email' => 'cliente@example.com',
        'phone' => '7777-8888',
    ]);

    $product = Product::create([
        'name' => 'Laptop Pro',
        'description' => 'Equipo de prueba',
        'sku' => 'SKU-001',
        'barcode' => 'BAR-001',
        'price' => 1200,
        'cost' => 800,
        'stock_min' => 2,
        'stock_max' => 50,
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'unit_id' => $unit->id,
        'status' => 'active',
    ]);

    $purchaseResponse = $this->postJson('/api/v1/purchases', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $mainWarehouse->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'cost' => 800,
            ],
        ],
    ]);

    $purchaseResponse
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft');

    $purchaseId = $purchaseResponse->json('data.id');

    $this->postJson("/api/v1/purchases/{$purchaseId}/confirm")
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed');

    $this->assertDatabaseHas('stocks', [
        'product_id' => $product->id,
        'warehouse_id' => $mainWarehouse->id,
        'quantity' => 10,
    ]);

    $saleResponse = $this->postJson('/api/v1/sales', [
        'customer_id' => $customer->id,
        'warehouse_id' => $mainWarehouse->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 4,
                'price' => 1200,
            ],
        ],
    ]);

    $saleResponse
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft');

    $saleId = $saleResponse->json('data.id');

    $this->postJson("/api/v1/sales/{$saleId}/confirm")
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed');

    $this->assertDatabaseHas('stocks', [
        'product_id' => $product->id,
        'warehouse_id' => $mainWarehouse->id,
        'quantity' => 6,
    ]);

    $transferResponse = $this->postJson('/api/v1/transfers', [
        'from_warehouse_id' => $mainWarehouse->id,
        'to_warehouse_id' => $secondaryWarehouse->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 3,
            ],
        ],
    ]);

    $transferResponse
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft');

    $transferId = $transferResponse->json('data.id');

    $this->postJson("/api/v1/transfers/{$transferId}/confirm")
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed');

    $this->assertDatabaseHas('stocks', [
        'product_id' => $product->id,
        'warehouse_id' => $mainWarehouse->id,
        'quantity' => 3,
    ]);

    $this->assertDatabaseHas('stocks', [
        'product_id' => $product->id,
        'warehouse_id' => $secondaryWarehouse->id,
        'quantity' => 3,
    ]);

    $this->assertDatabaseHas('purchases', [
        'id' => $purchaseId,
        'status' => 'confirmed',
    ]);

    $this->assertDatabaseHas('sales', [
        'id' => $saleId,
        'status' => 'confirmed',
    ]);

    $this->assertDatabaseHas('transfers', [
        'id' => $transferId,
        'status' => 'confirmed',
    ]);
});
