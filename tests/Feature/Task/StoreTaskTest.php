<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication to create tasks', function () {
    $this->postJson('/api/tasks', [
        'title' => 'Estudar Laravel',
    ])->assertUnauthorized();
});

it('creates a task for the authenticated user', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Estudar Laravel',
        'description' => 'Estudar Form Requests',
        'status' => 'pending',
        'priority' => 'high',
        'due_date' => '2026-10-15',
    ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Estudar Laravel')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.priority', 'high');

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Estudar Laravel',
        'priority' => 'high',
    ]);
});

it('validates required fields and enum values', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
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

it('rejects a client supplied user id', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Tarefa indevida',
        'user_id' => $otherUser->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');

    $this->assertDatabaseCount('tasks', 0);
});

it('uses default status and priority when omitted', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Tarefa com valores padrão',
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.priority', 'medium');
});

it('rejects null status and priority', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/tasks', [
        'title' => 'Estudar Laravel',
        'status' => null,
        'priority' => null,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'status',
            'priority',
        ]);
});

it('allows creating a task associated with the users project', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/tasks', [
        'title' => 'Implementar projetos',
        'project_id' => $project->id,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.project_id', $project->id);

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Implementar projetos',
    ]);
});

it('rejects creating a task in another users project', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $project = Project::factory()
        ->for($otherUser)
        ->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Tentativa indevida',
        'project_id' => $project->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id']);
});

it('rejects a nonexistent project when creating a task', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Nova tarefa',
        'project_id' => 999999999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id']);
});

it('allows creating a task without a project', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Tarefa independente',
    ])
        ->assertCreated();

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'project_id' => null,
        'title' => 'Tarefa independente',
    ]);
});
