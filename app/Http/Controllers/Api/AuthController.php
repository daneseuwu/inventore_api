<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\AuthLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(AuthLoginRequest $request)
    {
        $user = User::query()->where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return $this->error('Credenciales inválidas.', 401);
        }

        $token = $user->createToken($request->input('device_name', 'api'))->plainTextToken;

        return $this->success([
            'user' => $user,
            'token' => $token,
        ], 'Inicio de sesión exitoso.');
    }

    public function logout(Request $request)
    {
        try {
            $request->user()?->currentAccessToken()?->delete();

            return $this->success([], 'Sesión cerrada correctamente.');
        } catch (Throwable $e) {
            report($e);

            return $this->error('No se pudo cerrar la sesión.', 500);
        }
    }

    public function profile(Request $request)
    {
        return $this->success($request->user(), 'Perfil cargado correctamente.');
    }
}
