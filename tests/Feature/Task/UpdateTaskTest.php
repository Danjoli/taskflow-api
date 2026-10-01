<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication to update a task', function () {
    $task = Task::factory()->create();

    $this->patchJson("/api/tasks/{$task->id}", [
        'title' => 'Novo título',
    ])->assertUnauthorized();
});

it('allows the owner to update a task', function () {
    $user = User::factory()->create();

    $task = Task::factory()->for($user)->create([
        'title' => 'Título antigo',
        'status' => 'pending',
    ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", [
        'title' => 'Título atualizado',
        'status' => 'in_progress',
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Título atualizado')
        ->assertJsonPath('data.status', 'in_progress');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'user_id' => $user->id,
        'title' => 'Título atualizado',
        'status' => 'in_progress',
    ]);
});

it('preserves fields that were not submitted', function () {
    $user = User::factory()->create();

    $task = Task::factory()->for($user)->create([
        'title' => 'Título original',
        'priority' => 'high',
    ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", [
        'status' => 'completed',
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Título original')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.status', 'completed');
});

it('denies updates to tasks owned by another user', function () {
    $user = User::factory()->create();

    $task = Task::factory()->create([
        'title' => 'Tarefa privada',
    ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", [
        'title' => 'Tentativa de alteração',
    ])->assertForbidden();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Tarefa privada',
    ]);
});

it('returns not found when updating a non-existent task', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson('/api/tasks/999999', [
        'title' => 'Novo título',
    ])->assertNotFound();
});

it('rejects invalid task attributes', function () {
    $user = User::factory()->create();

    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", [
        'title' => '',
        'status' => 'invalid',
        'priority' => 'urgent',
        'due_date' => 'tomorrow',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'title',
            'status',
            'priority',
            'due_date',
        ]);
});

it('prevents changing the task owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", [
        'user_id' => $otherUser->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'user_id' => $user->id,
    ]);
});
