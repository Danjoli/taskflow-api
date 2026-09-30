<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs out the authenticated user and revokes the current token', function () {
    $user = User::factory()->create();

    $token = $user->createToken('api-token')->plainTextToken;

    expect($user->tokens()->count())->toBe(1);

    $response = $this->withToken($token)
        ->postJson('/api/logout');

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Logout realizado com sucesso.',
        ]);

    expect($user->tokens()->count())->toBe(0);
});

it('rejects unauthenticated logout requests', function () {
    $response = $this->postJson('/api/logout');

    $response->assertUnauthorized();
});

it('cannot access protected routes after logout', function () {
    $user = User::factory()->create();

    $token = $user->createToken('api-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);

    app('auth')->forgetGuards();

    $response = $this->withToken($token)
        ->getJson('/api/me');

    $response->assertUnauthorized();
});
