<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('allows authenticated users to list and create tasks', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Task::class))
        ->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Task::class))
        ->toBeTrue();
});

it('allows the owner to view update and delete a task', function () {
    $user = User::factory()->create();

    $task = Task::factory()
        ->for($user)
        ->create();

    expect(Gate::forUser($user)->allows('view', $task))
        ->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $task))
        ->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $task))
        ->toBeTrue();
});

it('denies access to tasks owned by another user', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $task = Task::factory()
        ->for($owner)
        ->create();

    expect(Gate::forUser($otherUser)->denies('view', $task))
        ->toBeTrue()
        ->and(Gate::forUser($otherUser)->denies('update', $task))
        ->toBeTrue()
        ->and(Gate::forUser($otherUser)->denies('delete', $task))
        ->toBeTrue();
});
