<?php

use App\Models\User;

it('returns a token on successful login', function (): void {
    User::factory()->admin()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ],
        ]);
});

it('returns standardized validation errors for login', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
    ]);

    $response
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Errores de validación.',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'errors' => [
                    'password',
                ],
            ],
        ]);
});

it('returns standardized unauthorized errors on protected endpoints', function (): void {
    $response = $this->getJson('/api/v1/products');

    $response
        ->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'No autenticado.',
            'data' => [],
        ]);
});
