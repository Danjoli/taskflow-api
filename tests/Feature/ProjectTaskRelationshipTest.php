<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

it('allows a project to have multiple tasks', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create();

    Task::factory()
        ->count(3)
        ->for($user)
        ->forProject($project)
        ->create();

    expect($project->tasks)
        ->toHaveCount(3);
});

it('allows a task to belong to a project', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create();

    $task = Task::factory()
        ->for($user)
        ->forProject($project)
        ->create();

    expect($task->project->is($project))
        ->toBeTrue();
});

it('allows a task to exist without a project', function () {
    $task = Task::factory()->create();

    expect($task->project_id)->toBeNull()
        ->and($task->project)->toBeNull();
});

it('keeps tasks when their project is deleted', function () {
    $user = User::factory()->create();

    $project = Project::factory()
        ->for($user)
        ->create();

    $task = Task::factory()
        ->for($user)
        ->forProject($project)
        ->create();

    $project->delete();

    $task->refresh();

    expect($task->project_id)->toBeNull()
        ->and($task->project)->toBeNull();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
    ]);
});
