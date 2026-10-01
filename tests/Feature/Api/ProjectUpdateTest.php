<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication to update a project', function () {
    $project = Project::factory()->create();

    $this->patchJson("/api/projects/{$project->id}", [
        'name' => 'Novo nome',
    ])->assertUnauthorized();
});

it('allows the owner to update a project', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create([
            'name' => 'Nome antigo',
            'description' => 'Descrição antiga',
        ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/projects/{$project->id}", [
        'name' => 'Nome atualizado',
        'description' => 'Descrição atualizada',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nome atualizado')
        ->assertJsonPath('data.description', 'Descrição atualizada');

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Nome atualizado',
        'description' => 'Descrição atualizada',
    ]);
});

it('allows partial updates without changing other fields', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create([
            'name' => 'Nome original',
            'description' => 'Descrição original',
        ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/projects/{$project->id}", [
        'name' => 'Novo nome',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Novo nome')
        ->assertJsonPath('data.description', 'Descrição original');
});

it('allows clearing the project description', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create([
            'description' => 'Descrição original',
        ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/projects/{$project->id}", [
        'description' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.description', null);
});

it('forbids updating another users project', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $project = Project::factory()
        ->for($otherUser)
        ->create([
            'name' => 'Projeto privado',
        ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/projects/{$project->id}", [
        'name' => 'Tentativa de alteração',
    ])->assertForbidden();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Projeto privado',
    ]);
});

it('rejects an empty project name', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create();

    Sanctum::actingAs($user);

    $this->patchJson("/api/projects/{$project->id}", [
        'name' => '',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('rejects project names longer than 150 characters', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create();

    Sanctum::actingAs($user);

    $this->patchJson("/api/projects/{$project->id}", [
        'name' => str_repeat('A', 151),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('prevents changing the project owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create();

    Sanctum::actingAs($user);

    $this->patchJson("/api/projects/{$project->id}", [
        'user_id' => $otherUser->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id']);

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'user_id' => $user->id,
    ]);
});

it('returns not found when updating a nonexistent project', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson('/api/projects/999999999', [
        'name' => 'Novo nome',
    ])->assertNotFound();
});
