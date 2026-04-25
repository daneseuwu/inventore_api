<?php

namespace App\Services\Inventory;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public const TYPE_IN = 'IN';
    public const TYPE_OUT = 'OUT';

    public function increase(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        User $user,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockMovement {
        return $this->adjust($product, $warehouse, $quantity, self::TYPE_IN, $user, $referenceType, $referenceId);
    }

    public function decrease(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        User $user,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockMovement {
        return $this->adjust($product, $warehouse, -$quantity, self::TYPE_OUT, $user, $referenceType, $referenceId);
    }

    public function transfer(
        Product $product,
        Warehouse $fromWarehouse,
        Warehouse $toWarehouse,
        int $quantity,
        User $user,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): array {
        return DB::transaction(function () use ($product, $fromWarehouse, $toWarehouse, $quantity, $user, $referenceType, $referenceId): array {
            $out = $this->decrease($product, $fromWarehouse, $quantity, $user, $referenceType, $referenceId);
            $in = $this->increase($product, $toWarehouse, $quantity, $user, $referenceType, $referenceId);

            return [$out, $in];
        });
    }

    public function manualMovement(
        Product $product,
        Warehouse $warehouse,
        string $type,
        int $quantity,
        User $user,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockMovement {
        $quantity = abs($quantity);

        return match ($type) {
            self::TYPE_IN => $this->increase($product, $warehouse, $quantity, $user, $referenceType, $referenceId),
            self::TYPE_OUT => $this->decrease($product, $warehouse, $quantity, $user, $referenceType, $referenceId),
            default => throw new RuntimeException('Tipo de movimiento inválido.'),
        };
    }

    public function currentStock(Product $product, ?Warehouse $warehouse = null): int
    {
        $query = Stock::query()->where('product_id', $product->getKey());

        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->getKey());
        }

        return (int) $query->sum('quantity');
    }

    protected function adjust(
        Product $product,
        Warehouse $warehouse,
        int $delta,
        string $type,
        User $user,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockMovement {
        if ($delta === 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($product, $warehouse, $delta, $type, $user, $referenceType, $referenceId): StockMovement {
            $stock = Stock::query()
                ->where('product_id', $product->getKey())
                ->where('warehouse_id', $warehouse->getKey())
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                try {
                    $stock = Stock::create([
                        'product_id' => $product->getKey(),
                        'warehouse_id' => $warehouse->getKey(),
                        'quantity' => 0,
                    ]);
                } catch (QueryException $exception) {
                    if (! $this->isUniqueStockViolation($exception)) {
                        throw $exception;
                    }

                    $stock = Stock::query()
                        ->where('product_id', $product->getKey())
                        ->where('warehouse_id', $warehouse->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $newQuantity = $stock->quantity + $delta;

            if ($newQuantity < 0) {
                throw new RuntimeException('Stock insuficiente para completar la operación.');
            }

            $stock->update([
                'quantity' => $newQuantity,
            ]);

            return StockMovement::create([
                'product_id' => $product->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'user_id' => $user->getKey(),
                'type' => $type,
                'quantity' => abs($delta),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        });
    }

    protected function isUniqueStockViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? (string) $exception->getCode();

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
