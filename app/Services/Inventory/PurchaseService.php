<?php

namespace App\Services\Inventory;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseService
{
    public function __construct(
        protected StockService $stockService
    ) {
    }

    public function createDraft(array $data): Purchase
    {
        return DB::transaction(function () use ($data): Purchase {
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'draft',
                'total' => 0,
            ]);

            $this->syncDetails($purchase, Arr::get($data, 'items', []));

            return $purchase->refresh()->load(['supplier', 'warehouse', 'details.product']);
        });
    }

    public function updateDraft(Purchase $purchase, array $data): Purchase
    {
        if (! $purchase->isDraft()) {
            throw new RuntimeException('Solo se pueden editar compras en borrador.');
        }

        return DB::transaction(function () use ($purchase, $data): Purchase {
            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'draft',
            ]);

            $purchase->details()->delete();
            $this->syncDetails($purchase, Arr::get($data, 'items', []));

            return $purchase->refresh()->load(['supplier', 'warehouse', 'details.product']);
        });
    }

    public function confirm(Purchase $purchase, User $user): Purchase
    {
        if ($purchase->isConfirmed()) {
            throw new RuntimeException('La compra ya fue confirmada.');
        }

        return DB::transaction(function () use ($purchase, $user): Purchase {
            $purchase->loadMissing(['warehouse', 'details.product']);

            foreach ($purchase->details as $detail) {
                $this->stockService->increase(
                    $detail->product,
                    $purchase->warehouse,
                    $detail->quantity,
                    $user,
                    Purchase::class,
                    $purchase->getKey()
                );
            }

            $purchase->update([
                'status' => 'confirmed',
            ]);

            return $purchase->refresh()->load(['supplier', 'warehouse', 'details.product']);
        });
    }

    protected function syncDetails(Purchase $purchase, array $items): void
    {
        $total = 0;

        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $cost = (float) $item['cost'];

            PurchaseDetail::create([
                'purchase_id' => $purchase->getKey(),
                'product_id' => $item['product_id'],
                'quantity' => $quantity,
                'cost' => $cost,
            ]);

            $total += $quantity * $cost;
        }

        $purchase->update([
            'total' => $total,
        ]);
    }
}
