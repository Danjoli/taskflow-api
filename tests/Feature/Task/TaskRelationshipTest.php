<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a user to have multiple tasks', function () {
    $user = User::factory()->create();

    Task::factory()
        ->count(3)
        ->for($user)
        ->create();

    expect($user->tasks()->count())->toBe(3);
});

it('associates each task with its owner', function () {
    $user = User::factory()->create();

    $task = Task::factory()
        ->for($user)
        ->create();

    expect($task->user->id)->toBe($user->id);
});

it('casts task status and priority to enums', function () {
    $task = Task::factory()->create();

    expect($task->status)->toBe(TaskStatus::Pending)
        ->and($task->priority)->toBe(TaskPriority::Medium);
});

it('deletes user tasks when the user is deleted', function () {
    $user = User::factory()->create();

    $task = Task::factory()
        ->for($user)
        ->create();

    $taskId = $task->id;

    $user->delete();

    $this->assertDatabaseMissing('tasks', [
        'id' => $taskId,
    ]);
});
