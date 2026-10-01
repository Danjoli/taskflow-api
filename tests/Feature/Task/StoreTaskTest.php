<?php

declare(strict_types=1);

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
