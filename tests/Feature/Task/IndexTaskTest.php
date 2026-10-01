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

    $this->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissingPath('data.0.user_id')
        ->assertJsonMissing(['id' => $otherTask->id]);

    expect(
        $ownTasks->pluck('id')->sort()->values()->all()
    )->toHaveCount(2);
});

it('returns an empty list when the user has no tasks', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks')
        ->assertOk()
        ->assertExactJson([
            'data' => [],
        ]);
});
