<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication to delete a task', function () {
    $task = Task::factory()->create();

    $this->deleteJson("/api/tasks/{$task->id}")
        ->assertUnauthorized();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
    ]);
});

it('allows the owner to delete a task', function () {
    $user = User::factory()->create();

    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/tasks/{$task->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});

it('denies deletion of a task owned by another user', function () {
    $user = User::factory()->create();

    $task = Task::factory()->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/tasks/{$task->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
    ]);
});

it('returns not found when deleting a non-existent task', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson('/api/tasks/999999')
        ->assertNotFound();
});

it('returns not found when deleting an already deleted task', function () {
    $user = User::factory()->create();

    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/tasks/{$task->id}")
        ->assertNoContent();

    $this->deleteJson("/api/tasks/{$task->id}")
        ->assertNotFound();
});
