<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the authenticated user', function () {
    $user = User::factory()->create([
        'email' => 'danilo@example.com',
    ]);

    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/me');

    $response
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonMissingPath('data.user.password');
});

it('rejects unauthenticated requests', function () {
    $response = $this->getJson('/api/me');

    $response->assertUnauthorized();
});
