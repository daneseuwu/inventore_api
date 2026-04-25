<?php

namespace App\Services\Inventory;

use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferService
{
    public function __construct(
        protected StockService $stockService
    ) {
    }

    public function createDraft(array $data): Transfer
    {
        return DB::transaction(function () use ($data): Transfer {
            $transfer = Transfer::create([
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'status' => 'draft',
            ]);

            $this->syncDetails($transfer, Arr::get($data, 'items', []));

            return $transfer->refresh()->load(['fromWarehouse', 'toWarehouse', 'details.product']);
        });
    }

    public function updateDraft(Transfer $transfer, array $data): Transfer
    {
        if (! $transfer->isDraft()) {
            throw new RuntimeException('Solo se pueden editar transferencias en borrador.');
        }

        return DB::transaction(function () use ($transfer, $data): Transfer {
            $transfer->update([
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'status' => 'draft',
            ]);

            $transfer->details()->delete();
            $this->syncDetails($transfer, Arr::get($data, 'items', []));

            return $transfer->refresh()->load(['fromWarehouse', 'toWarehouse', 'details.product']);
        });
    }

    public function confirm(Transfer $transfer, User $user): Transfer
    {
        if ($transfer->isConfirmed()) {
            throw new RuntimeException('La transferencia ya fue confirmada.');
        }

        return DB::transaction(function () use ($transfer, $user): Transfer {
            $transfer->loadMissing(['fromWarehouse', 'toWarehouse', 'details.product']);

            foreach ($transfer->details as $detail) {
                $this->stockService->transfer(
                    $detail->product,
                    $transfer->fromWarehouse,
                    $transfer->toWarehouse,
                    $detail->quantity,
                    $user,
                    Transfer::class,
                    $transfer->getKey()
                );
            }

            $transfer->update([
                'status' => 'confirmed',
            ]);

            return $transfer->refresh()->load(['fromWarehouse', 'toWarehouse', 'details.product']);
        });
    }

    protected function syncDetails(Transfer $transfer, array $items): void
    {
        foreach ($items as $item) {
            TransferDetail::create([
                'transfer_id' => $transfer->getKey(),
                'product_id' => $item['product_id'],
                'quantity' => (int) $item['quantity'],
            ]);
        }
    }
}
