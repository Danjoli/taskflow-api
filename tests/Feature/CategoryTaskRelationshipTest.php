<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Task;
use App\Models\User;

it('relates categories to their user and tasks', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $task = Task::factory()
        ->for($user)
        ->forCategory($category)
        ->create();

    expect($user->categories->first()->is($category))->toBeTrue()
        ->and($category->user->is($user))->toBeTrue()
        ->and($category->tasks->first()->is($task))->toBeTrue()
        ->and($task->category->is($category))->toBeTrue();
});

it('allows a task to exist without a category', function () {
    $task = Task::factory()->create();

    expect($task->category_id)->toBeNull()
        ->and($task->category)->toBeNull();
});

it('preserves tasks when their category is deleted', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $task = Task::factory()->for($user)->forCategory($category)->create();

    $category->delete();
    $task->refresh();

    expect($task->category_id)->toBeNull();
    $this->assertDatabaseHas('tasks', ['id' => $task->id]);
});
