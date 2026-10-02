<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication for comment endpoints', function () {
    $task = Task::factory()->create();
    $comment = Comment::factory()->for($task)->for($task->user)->create();

    $this->getJson("/api/tasks/{$task->id}/comments")->assertUnauthorized();
    $this->postJson("/api/tasks/{$task->id}/comments", ['body' => 'Note'])
        ->assertUnauthorized();
    $this->deleteJson("/api/tasks/{$task->id}/comments/{$comment->id}")
        ->assertUnauthorized();
});

it('lists owned task comments chronologically with their author', function () {
    $user = User::factory()->create(['name' => 'Daniel']);
    $task = Task::factory()->for($user)->create();
    Comment::factory()->for($task)->for($user)->create([
        'body' => 'First',
        'created_at' => now()->subMinute(),
    ]);
    Comment::factory()->for($task)->for($user)->create(['body' => 'Second']);
    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}/comments")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.body', 'First')
        ->assertJsonPath('data.1.body', 'Second')
        ->assertJsonPath('data.0.author.name', 'Daniel');
});

it('creates a comment with the authenticated user as author', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/tasks/{$task->id}/comments", ['body' => 'Progress note'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Progress note')
        ->assertJsonPath('data.author.id', $user->id);

    $this->assertDatabaseHas('comments', [
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => 'Progress note',
    ]);
});

it('validates comment content and prohibits ownership fields', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/tasks/{$task->id}/comments", [
        'body' => str_repeat('A', 2001),
        'user_id' => 999,
        'task_id' => 999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body', 'user_id', 'task_id']);
});

it('forbids listing and creating comments on another users task', function () {
    $task = Task::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/tasks/{$task->id}/comments")->assertForbidden();
    $this->postJson("/api/tasks/{$task->id}/comments", ['body' => 'No'])
        ->assertForbidden();
});

it('allows deleting a comment from its owned task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $comment = Comment::factory()->for($task)->for($user)->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/tasks/{$task->id}/comments/{$comment->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});

it('forbids deleting another users comment', function () {
    $task = Task::factory()->create();
    $comment = Comment::factory()->for($task)->for($task->user)->create();
    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson("/api/tasks/{$task->id}/comments/{$comment->id}")
        ->assertForbidden();
});

it('returns not found when a comment does not belong to the nested task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $otherTask = Task::factory()->for($user)->create();
    $comment = Comment::factory()->for($otherTask)->for($user)->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/tasks/{$task->id}/comments/{$comment->id}")
        ->assertNotFound();
});

it('does not provide an endpoint for editing comments', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $comment = Comment::factory()->for($task)->for($user)->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/tasks/{$task->id}/comments/{$comment->id}", [
        'body' => 'Edited',
    ])->assertMethodNotAllowed();
});
