<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;

it('relates a task to its subtasks', function () {
    $user = User::factory()->create();
    $parent = Task::factory()->for($user)->create();
    $subtask = Task::factory()->forParent($parent)->create();

    expect($parent->subtasks->first()->is($subtask))->toBeTrue()
        ->and($subtask->parent->is($parent))->toBeTrue()
        ->and($subtask->user->is($user))->toBeTrue();
});

it('deletes subtasks when their parent is deleted', function () {
    $parent = Task::factory()->create();
    $subtask = Task::factory()->forParent($parent)->create();

    $parent->delete();

    $this->assertDatabaseMissing('tasks', ['id' => $subtask->id]);
});
