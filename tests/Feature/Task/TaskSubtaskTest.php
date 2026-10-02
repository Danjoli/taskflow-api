<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('creates a subtask for an owned root task and exposes its status', function () {
    $user = User::factory()->create();
    $parent = Task::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Subtask',
        'parent_id' => $parent->id,
        'status' => 'in_progress',
    ])
        ->assertCreated()
        ->assertJsonPath('data.parent_id', $parent->id)
        ->assertJsonPath('data.status', 'in_progress');
});

it('rejects a parent task owned by another user', function () {
    $parent = Task::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/tasks', [
        'title' => 'Invalid subtask',
        'parent_id' => $parent->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);
});

it('prevents nesting subtasks beyond one level', function () {
    $user = User::factory()->create();
    $parent = Task::factory()->for($user)->create();
    $subtask = Task::factory()->forParent($parent)->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Nested subtask',
        'parent_id' => $subtask->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);
});

it('moves and detaches a subtask between root tasks', function () {
    $user = User::factory()->create();
    $firstParent = Task::factory()->for($user)->create();
    $secondParent = Task::factory()->for($user)->create();
    $subtask = Task::factory()->forParent($firstParent)->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$subtask->id}", [
        'parent_id' => $secondParent->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.parent_id', $secondParent->id);

    $this->patchJson("/api/tasks/{$subtask->id}", ['parent_id' => null])
        ->assertOk()
        ->assertJsonPath('data.parent_id', null);
});

it('prevents a task from becoming its own parent', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}", ['parent_id' => $task->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);
});

it('prevents a task with subtasks from becoming a subtask', function () {
    $user = User::factory()->create();
    $parent = Task::factory()->for($user)->create();
    Task::factory()->forParent($parent)->create();
    $otherParent = Task::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$parent->id}", [
        'parent_id' => $otherParent->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);
});

it('filters subtasks by an owned parent task', function () {
    $user = User::factory()->create();
    $parent = Task::factory()->for($user)->create();
    $subtask = Task::factory()->forParent($parent)->create();
    Task::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->getJson("/api/tasks?parent_id={$parent->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $subtask->id)
        ->assertJsonPath('meta.total', 1);
});

it('rejects filtering by another users parent task', function () {
    $parent = Task::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/tasks?parent_id={$parent->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);
});
