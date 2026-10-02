<?php

declare(strict_types=1);

use App\Enums\TaskActivityType;
use App\Models\Task;
use App\Models\User;

it('relates task activities to their task and actor', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $activity = $task->activities()->create([
        'user_id' => $user->id,
        'type' => TaskActivityType::Created,
        'changes' => ['title' => ['old' => null, 'new' => $task->title]],
    ]);

    expect($activity->task->is($task))->toBeTrue()
        ->and($activity->user->is($user))->toBeTrue()
        ->and($activity->type)->toBe(TaskActivityType::Created)
        ->and($user->taskActivities->first()->is($activity))->toBeTrue();
});

it('deletes task activities with their task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $activity = $task->activities()->create([
        'user_id' => $user->id,
        'type' => TaskActivityType::Created,
        'changes' => [],
    ]);

    $task->delete();

    $this->assertDatabaseMissing('task_activities', ['id' => $activity->id]);
});
