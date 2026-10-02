<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-10-02 23:30:00 UTC');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('filters overdue actionable tasks using UTC date boundaries', function () {
    $user = User::factory()->create();
    $overdue = Task::factory()->for($user)->create([
        'due_date' => '2026-10-01',
        'status' => 'pending',
    ]);
    Task::factory()->for($user)->create([
        'due_date' => '2026-10-02',
        'status' => 'pending',
    ]);
    Task::factory()->for($user)->create([
        'due_date' => '2026-10-01',
        'status' => 'completed',
    ]);
    Task::factory()->for($user)->create([
        'due_date' => '2026-10-01',
        'status' => 'cancelled',
    ]);
    Task::factory()->create([
        'due_date' => '2026-10-01',
        'status' => 'pending',
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/tasks?deadline=overdue')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $overdue->id)
        ->assertJsonPath('data.0.is_overdue', true);
});

it('filters due soon tasks inclusively with a configurable window', function () {
    $user = User::factory()->create();
    $today = Task::factory()->for($user)->create([
        'due_date' => '2026-10-02',
        'status' => 'in_progress',
    ]);
    $lastDay = Task::factory()->for($user)->create([
        'due_date' => '2026-10-09',
        'status' => 'pending',
    ]);
    Task::factory()->for($user)->create(['due_date' => '2026-10-10']);
    Task::factory()->for($user)->create([
        'due_date' => '2026-10-05',
        'status' => 'completed',
    ]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tasks?deadline=due_soon&days=7')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect(collect($response->json('data'))->pluck('id')->all())
        ->toEqualCanonicalizing([$today->id, $lastDay->id]);
});

it('uses seven days as the default due soon window', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['due_date' => '2026-10-09']);
    Task::factory()->for($user)->create(['due_date' => '2026-10-10']);
    Sanctum::actingAs($user);

    $this->getJson('/api/tasks?deadline=due_soon')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('reports overdue state on individual task responses', function () {
    $user = User::factory()->create();
    $overdue = Task::factory()->for($user)->create([
        'due_date' => '2026-10-01',
        'status' => 'pending',
    ]);
    $completed = Task::factory()->for($user)->create([
        'due_date' => '2026-10-01',
        'status' => 'completed',
    ]);
    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$overdue->id}")
        ->assertOk()
        ->assertJsonPath('data.is_overdue', true);

    $this->getJson("/api/tasks/{$completed->id}")
        ->assertOk()
        ->assertJsonPath('data.is_overdue', false);
});

it('validates deadline filters and their date window', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/tasks?deadline=invalid&days=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['deadline', 'days']);

    $this->getJson('/api/tasks?deadline=overdue&days=7')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['days']);

    $this->getJson('/api/tasks?days=7')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['days']);
});
