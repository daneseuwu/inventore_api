<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\PurchaseService;
use App\Services\Inventory\SaleService;
use App\Services\Inventory\TransferService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $alreadySeeded = Purchase::query()->exists() || Sale::query()->exists() || Transfer::query()->exists();

        $catalog = $this->seedCatalog();

        if ($alreadySeeded) {
            return;
        }

        $purchaseService = app(PurchaseService::class);
        $saleService = app(SaleService::class);
        $transferService = app(TransferService::class);

        $this->seedPurchases($purchaseService, $admin, $catalog);
        $this->seedTransfers($transferService, $admin, $catalog);
        $this->seedSales($saleService, $admin, $catalog);
    }

    protected function seedCatalog(): array
    {
        return DB::transaction(function (): array {
            $categories = $this->upsertCollection(Category::class, [
                ['name' => 'Electrónica', 'description' => 'Equipos y dispositivos tecnológicos.'],
                ['name' => 'Accesorios', 'description' => 'Periféricos y accesorios de oficina.'],
                ['name' => 'Oficina', 'description' => 'Consumibles y útiles de oficina.'],
                ['name' => 'Limpieza', 'description' => 'Insumos de limpieza y mantenimiento.'],
                ['name' => 'Alimentos', 'description' => 'Bebidas y alimentos de consumo interno.'],
                ['name' => 'Hardware', 'description' => 'Partes y componentes de cómputo.'],
            ], ['name']);

            $brands = $this->upsertCollection(Brand::class, [
                ['name' => 'Lenovo'],
                ['name' => 'Dell'],
                ['name' => 'Logitech'],
                ['name' => 'HP'],
                ['name' => '3M'],
                ['name' => 'Nestle'],
                ['name' => 'Samsung'],
                ['name' => 'APC'],
                ['name' => 'Truper'],
                ['name' => 'Aula'],
            ], ['name']);

            $units = $this->upsertCollection(Unit::class, [
                ['name' => 'Unidad', 'abbreviation' => 'ud'],
                ['name' => 'Caja', 'abbreviation' => 'cj'],
                ['name' => 'Paquete', 'abbreviation' => 'paq'],
                ['name' => 'Litro', 'abbreviation' => 'lt'],
                ['name' => 'Kilogramo', 'abbreviation' => 'kg'],
            ], ['name']);

            $warehouses = $this->upsertCollection(Warehouse::class, [
                ['name' => 'Bodega Central', 'location' => 'San Salvador'],
                ['name' => 'Sucursal Norte', 'location' => 'Santa Tecla'],
                ['name' => 'Sucursal Sur', 'location' => 'Soyapango'],
            ], ['name']);

            $suppliers = $this->upsertCollection(Supplier::class, [
                ['name' => 'Tech Distribuciones S.A. de C.V.', 'email' => 'ventas@techdistribuciones.com', 'phone' => '+503 2222-1001', 'address' => 'Zona Industrial, San Salvador'],
                ['name' => 'Suministros Globales S.A.', 'email' => 'contacto@suministrosglobales.com', 'phone' => '+503 2222-1002', 'address' => 'Avenida Las Américas, San Salvador'],
                ['name' => 'Office Pro Centroamérica', 'email' => 'ventas@officepro.com', 'phone' => '+503 2222-1003', 'address' => 'Santa Tecla, La Libertad'],
                ['name' => 'Comercial El Centro', 'email' => 'info@comercialelcentro.com', 'phone' => '+503 2222-1004', 'address' => 'Centro de San Salvador'],
            ], ['name']);

            $customers = $this->upsertCollection(Customer::class, [
                ['name' => 'Empresa Alpha S.A. de C.V.', 'email' => 'compras@empresaalpha.com', 'phone' => '+503 7777-1001'],
                ['name' => 'Colegio San José', 'email' => 'admin@colegiosanjose.edu.sv', 'phone' => '+503 7777-1002'],
                ['name' => 'Clínica Médica Vital', 'email' => 'facturacion@clinicavital.com', 'phone' => '+503 7777-1003'],
                ['name' => 'Taller Mecánico El Punto', 'email' => 'pagos@tallerepunto.com', 'phone' => '+503 7777-1004'],
                ['name' => 'Supermercado La Esquina', 'email' => 'cobros@supermercadolaesquina.com', 'phone' => '+503 7777-1005'],
            ], ['name']);

            $products = $this->upsertProducts($categories, $brands, $units);

            return compact('categories', 'brands', 'units', 'warehouses', 'suppliers', 'customers', 'products');
        });
    }

    protected function seedPurchases(PurchaseService $purchaseService, User $user, array $catalog): void
    {
        $supplier = $catalog['suppliers']->first();
        $warehouse = $catalog['warehouses']->firstWhere('name', 'Bodega Central');

        $purchaseBatches = [
            [
                'supplier' => $supplier,
                'items' => [
                    ['sku' => 'LAP-THINKPAD-E14', 'quantity' => 8],
                    ['sku' => 'MON-DELL-24P', 'quantity' => 14],
                    ['sku' => 'MOU-LOG-M170', 'quantity' => 45],
                    ['sku' => 'TEC-LOG-K120', 'quantity' => 35],
                    ['sku' => 'SSD-SAM-1TB', 'quantity' => 18],
                ],
            ],
            [
                'supplier' => $catalog['suppliers']->get(1),
                'items' => [
                    ['sku' => 'IMP-HP-135A', 'quantity' => 20],
                    ['sku' => 'TON-HP-126A', 'quantity' => 14],
                    ['sku' => 'PAP-A4-75-500', 'quantity' => 60],
                    ['sku' => 'EXT-3M-5M', 'quantity' => 22],
                    ['sku' => 'UPS-APC-900', 'quantity' => 8],
                ],
            ],
            [
                'supplier' => $catalog['suppliers']->get(2),
                'items' => [
                    ['sku' => 'CAF-PRIM-1KG', 'quantity' => 30],
                    ['sku' => 'AGU-500ML-24', 'quantity' => 90],
                    ['sku' => 'JAB-LIQ-1L', 'quantity' => 25],
                    ['sku' => 'DES-INP-1L', 'quantity' => 25],
                    ['sku' => 'AUD-AULA-MH001', 'quantity' => 20],
                ],
            ],
        ];

        foreach ($purchaseBatches as $batch) {
            $items = collect($batch['items'])->map(function (array $line) use ($catalog): array {
                $product = $catalog['products']->firstWhere('sku', $line['sku']);

                return [
                    'product_id' => $product->id,
                    'quantity' => $line['quantity'],
                    'cost' => $product->cost,
                ];
            })->all();

            $purchase = $purchaseService->createDraft([
                'supplier_id' => $batch['supplier']->id,
                'warehouse_id' => $warehouse->id,
                'items' => $items,
            ]);

            $purchaseService->confirm($purchase, $user);
        }
    }

    protected function seedTransfers(TransferService $transferService, User $user, array $catalog): void
    {
        $central = $catalog['warehouses']->firstWhere('name', 'Bodega Central');
        $north = $catalog['warehouses']->firstWhere('name', 'Sucursal Norte');
        $south = $catalog['warehouses']->firstWhere('name', 'Sucursal Sur');

        $transferBatches = [
            [
                'from' => $central,
                'to' => $north,
                'items' => [
                    ['sku' => 'LAP-THINKPAD-E14', 'quantity' => 2],
                    ['sku' => 'MOU-LOG-M170', 'quantity' => 12],
                    ['sku' => 'PAP-A4-75-500', 'quantity' => 10],
                ],
            ],
            [
                'from' => $central,
                'to' => $south,
                'items' => [
                    ['sku' => 'MON-DELL-24P', 'quantity' => 3],
                    ['sku' => 'TON-HP-126A', 'quantity' => 4],
                    ['sku' => 'CAF-PRIM-1KG', 'quantity' => 8],
                ],
            ],
        ];

        foreach ($transferBatches as $batch) {
            $items = collect($batch['items'])->map(function (array $line) use ($catalog): array {
                $product = $catalog['products']->firstWhere('sku', $line['sku']);

                return [
                    'product_id' => $product->id,
                    'quantity' => $line['quantity'],
                ];
            })->all();

            $transfer = $transferService->createDraft([
                'from_warehouse_id' => $batch['from']->id,
                'to_warehouse_id' => $batch['to']->id,
                'items' => $items,
            ]);

            $transferService->confirm($transfer, $user);
        }
    }

    protected function seedSales(SaleService $saleService, User $user, array $catalog): void
    {
        $central = $catalog['warehouses']->firstWhere('name', 'Bodega Central');
        $north = $catalog['warehouses']->firstWhere('name', 'Sucursal Norte');
        $south = $catalog['warehouses']->firstWhere('name', 'Sucursal Sur');

        $sales = [
            [
                'customer' => $catalog['customers']->firstWhere('name', 'Empresa Alpha S.A. de C.V.'),
                'warehouse' => $central,
                'items' => [
                    ['sku' => 'IMP-HP-135A', 'quantity' => 3],
                    ['sku' => 'SSD-SAM-1TB', 'quantity' => 2],
                    ['sku' => 'EXT-3M-5M', 'quantity' => 4],
                ],
            ],
            [
                'customer' => $catalog['customers']->firstWhere('name', 'Colegio San José'),
                'warehouse' => $north,
                'items' => [
                    ['sku' => 'LAP-THINKPAD-E14', 'quantity' => 1],
                    ['sku' => 'MOU-LOG-M170', 'quantity' => 4],
                    ['sku' => 'PAP-A4-75-500', 'quantity' => 3],
                ],
            ],
            [
                'customer' => $catalog['customers']->firstWhere('name', 'Clínica Médica Vital'),
                'warehouse' => $south,
                'items' => [
                    ['sku' => 'MON-DELL-24P', 'quantity' => 1],
                    ['sku' => 'TON-HP-126A', 'quantity' => 2],
                    ['sku' => 'CAF-PRIM-1KG', 'quantity' => 2],
                ],
            ],
            [
                'customer' => $catalog['customers']->firstWhere('name', 'Supermercado La Esquina'),
                'warehouse' => $central,
                'items' => [
                    ['sku' => 'AGU-500ML-24', 'quantity' => 18],
                    ['sku' => 'JAB-LIQ-1L', 'quantity' => 4],
                    ['sku' => 'DES-INP-1L', 'quantity' => 4],
                ],
            ],
        ];

        foreach ($sales as $saleData) {
            $items = collect($saleData['items'])->map(function (array $line) use ($catalog): array {
                $product = $catalog['products']->firstWhere('sku', $line['sku']);

                return [
                    'product_id' => $product->id,
                    'quantity' => $line['quantity'],
                    'price' => $product->price,
                ];
            })->all();

            $sale = $saleService->createDraft([
                'customer_id' => $saleData['customer']?->id,
                'warehouse_id' => $saleData['warehouse']->id,
                'items' => $items,
            ]);

            $saleService->confirm($sale, $user);
        }
    }

    protected function upsertProducts(Collection $categories, Collection $brands, Collection $units): Collection
    {
        $productData = [
            ['sku' => 'LAP-THINKPAD-E14', 'barcode' => '750100000001', 'name' => 'Laptop Lenovo ThinkPad E14', 'description' => 'Laptop empresarial de 14 pulgadas.', 'category' => 'Electrónica', 'brand' => 'Lenovo', 'unit' => 'Unidad', 'price' => 1299.00, 'cost' => 980.00, 'stock_min' => 5, 'stock_max' => 20],
            ['sku' => 'MON-DELL-24P', 'barcode' => '750100000002', 'name' => 'Monitor Dell 24 pulgadas', 'description' => 'Monitor IPS Full HD de 24".', 'category' => 'Electrónica', 'brand' => 'Dell', 'unit' => 'Unidad', 'price' => 189.00, 'cost' => 138.00, 'stock_min' => 10, 'stock_max' => 40],
            ['sku' => 'MOU-LOG-M170', 'barcode' => '750100000003', 'name' => 'Mouse Logitech M170', 'description' => 'Mouse inalámbrico compacto.', 'category' => 'Accesorios', 'brand' => 'Logitech', 'unit' => 'Unidad', 'price' => 19.90, 'cost' => 11.50, 'stock_min' => 20, 'stock_max' => 80],
            ['sku' => 'TEC-LOG-K120', 'barcode' => '750100000004', 'name' => 'Teclado Logitech K120', 'description' => 'Teclado USB de perfil bajo.', 'category' => 'Accesorios', 'brand' => 'Logitech', 'unit' => 'Unidad', 'price' => 24.50, 'cost' => 14.20, 'stock_min' => 15, 'stock_max' => 70],
            ['sku' => 'IMP-HP-135A', 'barcode' => '750100000005', 'name' => 'Impresora HP Laser 135A', 'description' => 'Impresora láser monocromática.', 'category' => 'Electrónica', 'brand' => 'HP', 'unit' => 'Unidad', 'price' => 239.00, 'cost' => 175.00, 'stock_min' => 4, 'stock_max' => 15],
            ['sku' => 'TON-HP-126A', 'barcode' => '750100000006', 'name' => 'Toner HP 126A', 'description' => 'Cartucho de tóner original.', 'category' => 'Oficina', 'brand' => 'HP', 'unit' => 'Unidad', 'price' => 59.90, 'cost' => 41.00, 'stock_min' => 8, 'stock_max' => 30],
            ['sku' => 'PAP-A4-75-500', 'barcode' => '750100000007', 'name' => 'Papel bond A4 75g 500 hojas', 'description' => 'Resma de papel bond.', 'category' => 'Oficina', 'brand' => '3M', 'unit' => 'Paquete', 'price' => 6.90, 'cost' => 4.20, 'stock_min' => 70, 'stock_max' => 200],
            ['sku' => 'CAF-PRIM-1KG', 'barcode' => '750100000008', 'name' => 'Café molido premium 1kg', 'description' => 'Café tostado y molido.', 'category' => 'Alimentos', 'brand' => 'Nestle', 'unit' => 'Kilogramo', 'price' => 12.50, 'cost' => 8.10, 'stock_min' => 25, 'stock_max' => 60],
            ['sku' => 'AGU-500ML-24', 'barcode' => '750100000009', 'name' => 'Agua embotellada 500ml x24', 'description' => 'Caja de 24 botellas.', 'category' => 'Alimentos', 'brand' => 'Nestle', 'unit' => 'Caja', 'price' => 4.75, 'cost' => 3.10, 'stock_min' => 20, 'stock_max' => 100],
            ['sku' => 'JAB-LIQ-1L', 'barcode' => '750100000010', 'name' => 'Jabón líquido 1L', 'description' => 'Insumo de limpieza para oficinas.', 'category' => 'Limpieza', 'brand' => 'Truper', 'unit' => 'Litro', 'price' => 3.95, 'cost' => 2.50, 'stock_min' => 15, 'stock_max' => 80],
            ['sku' => 'DES-INP-1L', 'barcode' => '750100000011', 'name' => 'Desinfectante multiusos 1L', 'description' => 'Desinfectante de alto rendimiento.', 'category' => 'Limpieza', 'brand' => 'Truper', 'unit' => 'Litro', 'price' => 4.60, 'cost' => 2.95, 'stock_min' => 15, 'stock_max' => 80],
            ['sku' => 'AUD-AULA-MH001', 'barcode' => '750100000012', 'name' => 'Audífonos Aula MH001', 'description' => 'Audífonos con micrófono.', 'category' => 'Accesorios', 'brand' => 'Aula', 'unit' => 'Unidad', 'price' => 28.00, 'cost' => 17.40, 'stock_min' => 12, 'stock_max' => 50],
            ['sku' => 'EXT-3M-5M', 'barcode' => '750100000013', 'name' => 'Extensión eléctrica 3M 5M', 'description' => 'Extensión con protección básica.', 'category' => 'Hardware', 'brand' => '3M', 'unit' => 'Unidad', 'price' => 9.80, 'cost' => 6.20, 'stock_min' => 10, 'stock_max' => 40],
            ['sku' => 'GAB-ATX-TRU', 'barcode' => '750100000014', 'name' => 'Gabinete ATX Truper', 'description' => 'Gabinete para PC de escritorio.', 'category' => 'Hardware', 'brand' => 'Truper', 'unit' => 'Unidad', 'price' => 68.00, 'cost' => 44.50, 'stock_min' => 6, 'stock_max' => 25],
            ['sku' => 'SSD-SAM-1TB', 'barcode' => '750100000015', 'name' => 'SSD Samsung 1TB', 'description' => 'Unidad de estado sólido de 1TB.', 'category' => 'Electrónica', 'brand' => 'Samsung', 'unit' => 'Unidad', 'price' => 89.00, 'cost' => 64.20, 'stock_min' => 20, 'stock_max' => 30],
            ['sku' => 'UPS-APC-900', 'barcode' => '750100000016', 'name' => 'UPS APC 900VA', 'description' => 'Respaldo de energía para oficina.', 'category' => 'Electrónica', 'brand' => 'APC', 'unit' => 'Unidad', 'price' => 149.00, 'cost' => 110.00, 'stock_min' => 5, 'stock_max' => 20],
        ];

        return collect($productData)->map(function (array $data) use ($categories, $brands, $units): Product {
            return Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'barcode' => $data['barcode'],
                    'price' => $data['price'],
                    'cost' => $data['cost'],
                    'stock_min' => $data['stock_min'],
                    'stock_max' => $data['stock_max'],
                    'category_id' => $categories->firstWhere('name', $data['category'])->id,
                    'brand_id' => $brands->firstWhere('name', $data['brand'])->id,
                    'unit_id' => $units->firstWhere('name', $data['unit'])->id,
                    'status' => 'active',
                ]
            );
        });
    }

    protected function upsertCollection(string $modelClass, array $records, array $uniqueBy): Collection
    {
        return collect($records)->map(function (array $record) use ($modelClass, $uniqueBy) {
            $lookup = array_intersect_key($record, array_flip($uniqueBy));

            return $modelClass::updateOrCreate($lookup, $record);
        });
    }
}
