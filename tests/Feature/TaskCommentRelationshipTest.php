<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;

it('relates comments to their task and author', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $comment = Comment::factory()->for($task)->for($user)->create();

    expect($task->comments->first()->is($comment))->toBeTrue()
        ->and($user->comments->first()->is($comment))->toBeTrue()
        ->and($comment->task->is($task))->toBeTrue()
        ->and($comment->user->is($user))->toBeTrue();
});

it('deletes comments when their task is deleted', function () {
    $task = Task::factory()->create();
    $comment = Comment::factory()
        ->for($task)
        ->for($task->user)
        ->create();

    $task->delete();

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});
