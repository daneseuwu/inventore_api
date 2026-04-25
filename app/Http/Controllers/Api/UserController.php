<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use RuntimeException;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(UserIndexRequest $request)
    {
        $query = User::query()
            ->when($request->validated('search'), function ($query, string $search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->validated('role'), fn ($query, $role) => $query->where('role', $role))
            ->orderBy('name');

        return $this->paginated($query->paginate($request->integer('per_page', 15)), 'Usuarios listados correctamente.');
    }

    public function store(UserRequest $request)
    {
        try {
            $user = User::create($request->validated());

            return $this->success($user, 'Usuario creado correctamente.', 201);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(User $user)
    {
        return $this->success($user, 'Usuario cargado correctamente.');
    }

    public function update(UserRequest $request, User $user)
    {
        try {
            $payload = [
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'role' => $request->validated('role'),
            ];

            if ($request->filled('password')) {
                $payload['password'] = $request->validated('password');
            }

            $user->update($payload);

            return $this->success($user->refresh(), 'Usuario actualizado correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();

            return $this->success([], 'Usuario eliminado correctamente.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return $this->error($e->getMessage(), 422);
        }
    }
}
