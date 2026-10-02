<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication to view a task', function () {
    $task = Task::factory()->create();

    $this->getJson("/api/tasks/{$task->id}")
        ->assertUnauthorized();
});

it('allows the owner to view a task', function () {
    $user = User::factory()->create();

    $task = Task::factory()->for($user)->create([
        'title' => 'Estudar Laravel',
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $task->id)
        ->assertJsonPath('data.title', 'Estudar Laravel')
        ->assertJsonMissingPath('data.user_id');
});

it('returns the project id associated with the task', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create();

    $task = Task::factory()
        ->for($user)
        ->forProject($project)
        ->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.project_id', $project->id);
});

it('denies access to a task owned by another user', function () {
    $user = User::factory()->create();

    $task = Task::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}")
        ->assertForbidden();
});

it('returns not found for a non-existent task', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks/999999')
        ->assertNotFound();
});
