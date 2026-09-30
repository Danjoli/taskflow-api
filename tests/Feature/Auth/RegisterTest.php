<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a user and returns an access token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Danilo',
        'email' => 'danilo@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Usuário cadastrado com sucesso.')
        ->assertJsonPath('data.user.email', 'danilo@example.com')
        ->assertJsonStructure([
            'data' => ['user', 'token'],
        ])
        ->assertJsonMissingPath('data.user.password');

    $this->assertDatabaseHas('users', [
        'email' => 'danilo@example.com',
    ]);

    expect(User::first()->tokens()->count())->toBe(1);
});
