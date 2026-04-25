<?php

namespace App\Services\Inventory;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleService
{
    public function __construct(
        protected StockService $stockService
    ) {
    }

    public function createDraft(array $data): Sale
    {
        return DB::transaction(function () use ($data): Sale {
            $sale = Sale::create([
                'customer_id' => $data['customer_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'draft',
                'total' => 0,
            ]);

            $this->syncDetails($sale, Arr::get($data, 'items', []));

            return $sale->refresh()->load(['customer', 'warehouse', 'details.product']);
        });
    }

    public function updateDraft(Sale $sale, array $data): Sale
    {
        if (! $sale->isDraft()) {
            throw new RuntimeException('Solo se pueden editar ventas en borrador.');
        }

        return DB::transaction(function () use ($sale, $data): Sale {
            $sale->update([
                'customer_id' => $data['customer_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'draft',
            ]);

            $sale->details()->delete();
            $this->syncDetails($sale, Arr::get($data, 'items', []));

            return $sale->refresh()->load(['customer', 'warehouse', 'details.product']);
        });
    }

    public function confirm(Sale $sale, User $user): Sale
    {
        if ($sale->isConfirmed()) {
            throw new RuntimeException('La venta ya fue confirmada.');
        }

        return DB::transaction(function () use ($sale, $user): Sale {
            $sale->loadMissing(['warehouse', 'details.product']);

            foreach ($sale->details as $detail) {
                $this->stockService->decrease(
                    $detail->product,
                    $sale->warehouse,
                    $detail->quantity,
                    $user,
                    Sale::class,
                    $sale->getKey()
                );
            }

            $sale->update([
                'status' => 'confirmed',
            ]);

            return $sale->refresh()->load(['customer', 'warehouse', 'details.product']);
        });
    }

    protected function syncDetails(Sale $sale, array $items): void
    {
        $total = 0;

        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $price = (float) $item['price'];

            SaleDetail::create([
                'sale_id' => $sale->getKey(),
                'product_id' => $item['product_id'],
                'quantity' => $quantity,
                'price' => $price,
            ]);

            $total += $quantity * $price;
        }

        $sale->update([
            'total' => $total,
        ]);
    }
}
