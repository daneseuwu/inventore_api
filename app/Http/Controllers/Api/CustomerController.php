<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use RuntimeException;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index()
    {
        return $this->paginated(Customer::query()->latest()->paginate(15), 'Clientes listados correctamente.');
    }

    public function store(CustomerRequest $request)
    {
        try {
            $customer = Customer::create($request->validated());

            return $this->success($customer, 'Cliente creado correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Customer $customer)
    {
        return $this->success($customer->load('sales'), 'Cliente cargado correctamente.');
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        try {
            $customer->update($request->validated());

            return $this->success($customer->refresh(), 'Cliente actualizado correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Customer $customer)
    {
        try {
            $customer->delete();

            return $this->success([], 'Cliente eliminado correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
