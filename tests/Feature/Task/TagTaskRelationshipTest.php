<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;

it('relates tags and tasks through a many to many relationship', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $tags = Tag::factory()->count(2)->for($user)->create();

    $task->tags()->attach($tags);

    expect($task->tags)->toHaveCount(2)
        ->and($tags->first()->tasks->first()->is($task))->toBeTrue()
        ->and($user->tags)->toHaveCount(2);
});

it('removes associations without deleting tasks when a tag is deleted', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $tag = Tag::factory()->for($user)->create();
    $task->tags()->attach($tag);

    $tag->delete();

    expect($task->tags()->count())->toBe(0);
    $this->assertDatabaseHas('tasks', ['id' => $task->id]);
});
