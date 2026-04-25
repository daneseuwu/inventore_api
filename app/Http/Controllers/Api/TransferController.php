<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\TransferIndexRequest;
use App\Http\Requests\TransferRequest;
use App\Models\Transfer;
use App\Services\Inventory\TransferService;
use RuntimeException;

class TransferController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(Transfer::class, 'transfer');
    }

    public function index(TransferIndexRequest $request)
    {
        $query = Transfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'details.product'])
            ->when($request->validated('from_warehouse_id'), fn ($query, $warehouseId) => $query->where('from_warehouse_id', $warehouseId))
            ->when($request->validated('to_warehouse_id'), fn ($query, $warehouseId) => $query->where('to_warehouse_id', $warehouseId))
            ->when($request->validated('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest();

        return $this->paginated($query->paginate($request->integer('per_page', 15)), 'Transferencias listadas correctamente.');
    }

    public function store(TransferRequest $request, TransferService $transferService)
    {
        try {
            $transfer = $transferService->createDraft($request->validated());

            return $this->success($transfer, 'Transferencia creada correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Transfer $transfer)
    {
        return $this->success($transfer->load(['fromWarehouse', 'toWarehouse', 'details.product']), 'Transferencia cargada correctamente.');
    }

    public function update(TransferRequest $request, Transfer $transfer, TransferService $transferService)
    {
        try {
            $transfer = $transferService->updateDraft($transfer, $request->validated());

            return $this->success($transfer, 'Transferencia actualizada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Transfer $transfer)
    {
        try {
            $transfer->delete();

            return $this->success([], 'Transferencia eliminada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function confirm(Transfer $transfer, TransferService $transferService)
    {
        try {
            $this->authorize('confirm', $transfer);

            $transfer = $transferService->confirm($transfer, request()->user());

            return $this->success($transfer, 'Transferencia confirmada correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
