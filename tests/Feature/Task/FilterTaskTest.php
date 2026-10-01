<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('filters tasks by status', function () {
    $user = User::factory()->create();

    $pendingTask = Task::factory()->for($user)->create([
        'status' => 'pending',
    ]);

    Task::factory()->for($user)->create([
        'status' => 'completed',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks?status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pendingTask->id)
        ->assertJsonPath('meta.total', 1);
});

it('filters tasks by priority', function () {
    $user = User::factory()->create();

    $highPriorityTask = Task::factory()->for($user)->create([
        'priority' => 'high',
    ]);

    Task::factory()->for($user)->create([
        'priority' => 'low',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks?priority=high')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $highPriorityTask->id);
});

it('filters tasks by due date', function () {
    $user = User::factory()->create();

    $task = Task::factory()->for($user)->create([
        'due_date' => '2026-10-15',
    ]);

    Task::factory()->for($user)->create([
        'due_date' => '2026-10-20',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks?due_date=2026-10-15')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $task->id);
});

it('combines multiple filters', function () {
    $user = User::factory()->create();

    $matchingTask = Task::factory()->for($user)->create([
        'status' => 'pending',
        'priority' => 'high',
        'due_date' => '2026-10-15',
    ]);

    Task::factory()->for($user)->create([
        'status' => 'pending',
        'priority' => 'low',
        'due_date' => '2026-10-15',
    ]);

    Task::factory()->for($user)->create([
        'status' => 'completed',
        'priority' => 'high',
        'due_date' => '2026-10-15',
    ]);

    Sanctum::actingAs($user);

    $this->getJson(
        '/api/tasks?status=pending&priority=high&due_date=2026-10-15'
    )
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingTask->id);
});

it('rejects invalid filter values', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(
        '/api/tasks?status=invalid&priority=urgent&due_date=tomorrow&page=0'
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'status',
            'priority',
            'due_date',
            'page',
        ]);
});

it('does not return another users tasks when filtering', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownTask = Task::factory()->for($user)->create([
        'status' => 'pending',
    ]);

    Task::factory()->for($otherUser)->create([
        'status' => 'pending',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks?status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownTask->id)
        ->assertJsonPath('meta.total', 1);
});

it('preserves filters in pagination links', function () {
    $user = User::factory()->create();

    Task::factory()
        ->count(20)
        ->for($user)
        ->create([
            'status' => 'pending',
        ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tasks?status=pending&page=1');

    $response
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.total', 20);

    expect($response->json('links.next'))
        ->toContain('status=pending')
        ->toContain('page=2');
});
