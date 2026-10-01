<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication to create projects', function () {
    $this->postJson('/api/projects', [
        'name' => 'Meu projeto',
    ])->assertUnauthorized();
});

it('creates a project for the authenticated user', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/projects', [
        'name' => 'TaskFlow Backend',
        'description' => 'Desenvolvimento da API',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'TaskFlow Backend')
        ->assertJsonPath('data.description', 'Desenvolvimento da API');

    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'name' => 'TaskFlow Backend',
    ]);
});

it('requires a project name', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/projects', [
        'description' => 'Projeto sem nome',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('rejects project names longer than 150 characters', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/projects', [
        'name' => str_repeat('A', 151),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('does not allow assigning projects to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/projects', [
        'name' => 'Projeto indevido',
        'user_id' => $otherUser->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id']);

    expect(Project::query()->count())->toBe(0);
});

it('allows creating a project without a description', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/projects', [
        'name' => 'Projeto simples',
    ])
        ->assertCreated()
        ->assertJsonPath('data.description', null);
});
