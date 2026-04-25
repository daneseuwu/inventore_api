<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

it('downloads the products spreadsheet', function (): void {
    Sanctum::actingAs(User::factory()->admin()->create());

    Carbon::setTestNow('2026-04-24 12:00:00');

    $response = $this->get('/api/v1/exports/products');

    $response->assertOk();
    $response->assertDownload('products-20260424_120000.xlsx');
});

it('imports products from csv', function (): void {
    Sanctum::actingAs(User::factory()->admin()->create());

    $csv = <<<CSV
name,description,sku,barcode,price,cost,stock_min,stock_max,category_name,brand_name,unit_name,status
Laptop Demo,Equipo de prueba,LAP-DEMO,750000000501,1200,800,2,20,Tecnologia,Acme,Unidad,active
CSV;

    $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

    $response = $this->post('/api/v1/imports/products', [
        'file' => $file,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.created', 1)
        ->assertJsonPath('data.updated', 0)
        ->assertJsonPath('data.errors', []);

    $this->assertDatabaseHas('products', [
        'sku' => 'LAP-DEMO',
        'name' => 'Laptop Demo',
    ]);
});
