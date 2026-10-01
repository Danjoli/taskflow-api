<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication to list tasks', function () {
    $this->getJson('/api/tasks')
        ->assertUnauthorized();
});

it('returns only tasks belonging to the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownTasks = Task::factory()
        ->count(2)
        ->for($user)
        ->create();

    $otherTask = Task::factory()
        ->for($otherUser)
        ->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tasks');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissingPath('data.0.user_id')
        ->assertJsonMissing(['id' => $otherTask->id]);

    $returnedIds = collect($response->json('data'))
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    $expectedIds = $ownTasks
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    expect($returnedIds)->toBe($expectedIds);
});

it('returns an empty list when the user has no tasks', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('meta.current_page', 1);
});

it('paginates tasks belonging to the authenticated user', function () {
    $user = User::factory()->create();

    $tasks = Task::factory()
        ->count(20)
        ->for($user)
        ->create();

    Sanctum::actingAs($user);

    $firstPage = $this->getJson('/api/tasks?page=1');

    $firstPage
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.total', 20);

    $secondPage = $this->getJson('/api/tasks?page=2');

    $secondPage
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.current_page', 2);

    $returnedIds = collect([
        ...$firstPage->json('data'),
        ...$secondPage->json('data'),
    ])
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    $expectedIds = $tasks
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    expect($returnedIds)->toBe($expectedIds);
});
