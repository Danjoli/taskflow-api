<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs in a user and returns an access token', function () {
    $user = User::factory()->create([
        'email' => 'danilo@example.com',
        'password' => 'Password123!',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'danilo@example.com',
        'password' => 'Password123!',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Login realizado com sucesso.')
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonStructure([
            'data' => [
                'user',
                'token',
            ],
        ])
        ->assertJsonMissingPath('data.user.password');

    expect($user->tokens()->count())->toBe(1);
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'danilo@example.com',
        'password' => 'Password123!',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'danilo@example.com',
        'password' => 'WrongPassword!',
    ]);

    $response
        ->assertUnauthorized()
        ->assertJson([
            'message' => 'As credenciais fornecidas são inválidas.',
        ]);
});

it('validates required login fields', function () {
    $response = $this->postJson('/api/login', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email',
            'password',
        ]);
});
